#!/bin/bash

# 智能部署腳本 - Laravel Wallet 專案
# 支援環境選擇、自動部署、狀態檢查
# Author: Roy | Date: 2025-08-30

set -e

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# 自動偵測專案根目錄
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DEPLOYMENT_ROOT="$SCRIPT_DIR"
PROJECT_ROOT="$(dirname "$DEPLOYMENT_ROOT")"

# 印出帶顏色的訊息
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_header() {
    echo -e "${PURPLE}$1${NC}"
}

# 顯示標題
show_banner() {
    clear
    echo -e "${WHITE}"
    echo "╔══════════════════════════════════════════════════════════╗"
    echo "║                   🚀 智能部署腳本                        ║"
    echo "║              Laravel Wallet 專案部署工具                 ║"
    echo "║                                                          ║"
    echo "║  功能：環境選擇 | 自動部署 | 狀態檢查 | 服務監控           ║"
    echo "╚══════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

# 顯示環境選單
show_environment_menu() {
    echo -e "${CYAN}"
    echo "請選擇部署環境："
    echo "1) 🔧 開發環境 (develop)"
    echo "2) 🏭 生產環境 (production)"
    echo "3) 📊 查看當前狀態"
    echo "4) 🧹 清理所有容器"
    echo "5) ❌ 退出"
    echo -e "${NC}"
}

# 檢查 Docker 狀態
check_docker_status() {
    local env_dir=$1
    local env_name=$2
    
    print_header "=== Docker 容器狀態檢查 ($env_name) ==="
    
    cd "$env_dir"
    
    # 檢查容器狀態
    echo -e "${YELLOW}📦 容器狀態：${NC}"
    $DOCKER_COMPOSE_CMD ps
    
    echo -e "\n${YELLOW}💾 映像大小：${NC}"
    docker images | grep -E "(wallet|php|nginx|mysql)" | head -10
    
    echo -e "\n${YELLOW}📈 資源使用：${NC}"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}" | head -10
}

# 檢查服務健康狀態
check_service_health() {
    local env_dir=$1
    local env_name=$2
    
    print_header "=== 服務健康檢查 ($env_name) ==="
    
    cd "$env_dir"
    
    # 檢查 PHP 服務
    echo -e "${YELLOW}🐘 PHP 服務檢查：${NC}"
    if $DOCKER_COMPOSE_CMD exec -T php php -v 2>/dev/null; then
        print_success "PHP 服務正常"
    else
        print_error "PHP 服務異常"
    fi
    
    # 檢查 Laravel 應用
    echo -e "\n${YELLOW}🌐 Laravel 應用檢查：${NC}"
    if $DOCKER_COMPOSE_CMD exec -T php php artisan --version 2>/dev/null; then
        print_success "Laravel 應用正常"
    else
        print_error "Laravel 應用異常"
    fi
    
    # 檢查資料庫連線
    echo -e "\n${YELLOW}🗄️  資料庫連線檢查：${NC}"
    if $DOCKER_COMPOSE_CMD exec -T php php artisan migrate:status 2>/dev/null | head -5; then
        print_success "資料庫連線正常"
    else
        print_warning "資料庫連線需要檢查"
    fi
    
    # 檢查 Octane 狀態
    echo -e "\n${YELLOW}⚡ Octane 服務檢查：${NC}"
    if $DOCKER_COMPOSE_CMD exec -T php php artisan octane:status 2>/dev/null; then
        print_success "Octane 服務正常"
    else
        print_warning "Octane 服務未啟動或需要檢查"
    fi
    
    # 檢查端口狀態
    echo -e "\n${YELLOW}🔌 端口狀態檢查：${NC}"
    echo "檢查常用端口..."
    for port in 80 3306 8000 9000; do
        if netstat -ln | grep ":$port " >/dev/null 2>&1; then
            print_success "端口 $port 正在使用中"
        else
            print_warning "端口 $port 未使用"
        fi
    done
}

# 執行部署
deploy_environment() {
    local env_name=$1
    local env_dir="$DEPLOYMENT_ROOT/$env_name"
    
    if [ ! -d "$env_dir" ]; then
        print_error "環境目錄不存在: $env_dir"
        return 1
    fi
    
    print_header "=== 開始部署 $env_name 環境 ==="
    
    cd "$env_dir"
    
    # 停止現有容器
    print_info "停止現有容器..."
    $DOCKER_COMPOSE_CMD down
    
    # 清理舊映像 (可選)
    read -p "是否要清理舊的 Docker 映像？(y/N): " cleanup_images
    case "$cleanup_images" in
        [Yy]|[Yy][Ee][Ss])
            print_info "清理舊映像..."
            docker system prune -f
            ;;
    esac
    
    # 選擇運行模式
    echo ""
    echo -e "${YELLOW}選擇 PHP 運行模式：${NC}"
    echo "1) 🚀 Octane (Swoole) - 高性能異步服務"
    echo "2) 🐘 PHP-FPM - 傳統 FastCGI 處理"
    echo "3) 🔄 Both - 同時運行兩種模式"
    echo ""
    read -p "請選擇運行模式 (1-3, 預設為 1): " run_mode_choice
    
    case "$run_mode_choice" in
        2)
            RUN_MODE="fpm"
            print_info "選擇運行模式: PHP-FPM"
            ;;
        3)
            RUN_MODE="both"
            print_info "選擇運行模式: Octane + PHP-FPM"
            ;;
        *)
            RUN_MODE="octane"
            print_info "選擇運行模式: Octane (Swoole)"
            ;;
    esac

    # 開始建置
    print_info "開始建置 Docker 映像..."
    local start_time=$(date +%s)
    
    if RUN_MODE=$RUN_MODE $DOCKER_COMPOSE_CMD up -d --build; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        print_success "部署成功！建置時間：${duration}秒"
    else
        print_error "部署失敗！"
        return 1
    fi
    
    # 等待服務啟動
    print_info "等待服務啟動..."
    sleep 10
    
    # 執行 Composer 安裝
    print_info "安裝 Composer 依賴..."
    if $DOCKER_COMPOSE_CMD exec -T php composer install --no-dev --optimize-autoloader; then
        print_success "Composer 依賴安裝完成"
    else
        print_warning "Composer 依賴安裝可能有問題"
    fi
    
    # 自動執行檢查
    check_docker_status "$env_dir" "$env_name"
    echo ""
    check_service_health "$env_dir" "$env_name"
    
    # 顯示訪問資訊
    print_header "=== 部署完成 ==="
    echo -e "${GREEN}🎉 $env_name 環境部署成功！${NC}"
    echo -e "${CYAN}📍 服務訪問資訊：${NC}"
    echo "   - Web 服務: http://localhost"
    echo "   - API 服務: http://localhost:8000"
    echo "   - 資料庫: localhost:3306"
    echo ""
    echo -e "${YELLOW}💡 常用指令：${NC}"
    echo "   - 查看日誌: $DOCKER_COMPOSE_CMD logs -f"
    echo "   - 進入容器: $DOCKER_COMPOSE_CMD exec php bash"
    echo "   - 重啟服務: $DOCKER_COMPOSE_CMD restart"
}

# 查看當前狀態
show_current_status() {
    print_header "=== 當前環境狀態總覽 ==="
    
    for env in develop production; do
        local env_dir="$DEPLOYMENT_ROOT/$env"
        if [ -d "$env_dir" ]; then
            echo -e "\n${PURPLE}--- $env 環境 ---${NC}"
            cd "$env_dir"
            
            # 檢查是否有運行的容器
            local running_containers=$($DOCKER_COMPOSE_CMD ps -q)
            if [ -n "$running_containers" ]; then
                print_success "$env 環境有運行中的容器"
                $DOCKER_COMPOSE_CMD ps
            else
                print_warning "$env 環境沒有運行中的容器"
            fi
        fi
    done
}

# 清理所有容器
cleanup_all() {
    print_warning "即將清理所有 Docker 容器和映像"
    read -p "確認要繼續嗎？這將停止所有容器並清理系統 (y/N): " confirm
    
    case "$confirm" in
        [Yy]|[Yy][Ee][Ss])
            print_info "停止所有容器..."
            
            # 停止各環境的容器
            for env in develop production; do
                local env_dir="$DEPLOYMENT_ROOT/$env"
                if [ -d "$env_dir" ]; then
                    cd "$env_dir"
                    $DOCKER_COMPOSE_CMD down
                fi
            done
            
            # 清理系統
            print_info "清理 Docker 系統..."
            docker system prune -af
            print_success "清理完成"
            ;;
        *)
            print_info "取消清理操作"
            ;;
    esac
}

# 主選單循環
main_menu() {
    while true; do
        show_banner
        show_environment_menu
        
        read -p "請輸入選項 (1-5): " choice
        
        case $choice in
            1)
                deploy_environment "develop"
                read -p "按 Enter 鍵繼續..."
                ;;
            2)
                deploy_environment "production"
                read -p "按 Enter 鍵繼續..."
                ;;
            3)
                show_current_status
                read -p "按 Enter 鍵繼續..."
                ;;
            4)
                cleanup_all
                read -p "按 Enter 鍵繼續..."
                ;;
            5)
                print_success "感謝使用智能部署腳本！"
                exit 0
                ;;
            *)
                print_error "無效選項，請重新選擇"
                sleep 2
                ;;
        esac
    done
}

# 檢查必要工具
check_requirements() {
    local missing_tools=""
    
    # 檢查 Docker
    if ! command -v docker >/dev/null 2>&1; then
        missing_tools="docker"
    fi
    
    # 檢查 Docker Compose (支援新舊版本)
    local has_compose=false
    if command -v docker-compose >/dev/null 2>&1; then
        has_compose=true
        DOCKER_COMPOSE_CMD="docker-compose"
    elif docker compose version >/dev/null 2>&1; then
        has_compose=true
        DOCKER_COMPOSE_CMD="docker compose"
    fi
    
    if [ "$has_compose" = false ]; then
        if [ -z "$missing_tools" ]; then
            missing_tools="docker-compose"
        else
            missing_tools="$missing_tools docker-compose"
        fi
    fi
    
    if [ -n "$missing_tools" ]; then
        print_error "缺少必要工具: $missing_tools"
        echo "請先安裝 Docker 和 Docker Compose"
        echo "提示: 新版 Docker 可能使用 'docker compose' 而非 'docker-compose'"
        exit 1
    fi
    
    print_info "使用 Docker Compose 指令: $DOCKER_COMPOSE_CMD"
}

# 主程式入口
main() {
    check_requirements
    
    # 檢查專案目錄
    if [ ! -d "$PROJECT_ROOT" ]; then
        print_error "專案目錄不存在: $PROJECT_ROOT"
        exit 1
    fi
    
    if [ ! -d "$DEPLOYMENT_ROOT" ]; then
        print_error "部署目錄不存在: $DEPLOYMENT_ROOT"
        exit 1
    fi
    
    # 顯示偵測到的路徑資訊
    print_info "專案根目錄: $PROJECT_ROOT"
    print_info "部署目錄: $DEPLOYMENT_ROOT"
    
    # 進入主選單
    main_menu
}

# 執行主程式
main "$@"
