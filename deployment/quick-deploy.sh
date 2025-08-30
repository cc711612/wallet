#!/bin/bash

# 快速部署腳本 - 簡化版
# 快速進入 deployment 目錄並選擇環境

# 自動偵測路徑
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# 顏色定義
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 快速部署工具${NC}"
echo "1) 開發環境 (develop)"
echo "2) 生產環境 (production)"
echo "3) 智能部署 (完整功能)"

read -p "選擇選項 (1-3): " choice

case $choice in
    1)
        echo -e "${GREEN}切換到開發環境...${NC}"
        cd "$SCRIPT_DIR/develop"
        echo -e "${YELLOW}當前位置: $(pwd)${NC}"
        echo -e "${YELLOW}可用指令:${NC}"
        echo "  - RUN_MODE=octane docker-compose up -d --build  # 重新建置並啟動"
        echo "  - docker-compose up -d                         # 啟動服務"
        echo "  - docker-compose logs -f                       # 查看日誌"
        echo "  - docker-compose exec php bash                 # 進入容器"
        exec bash
        ;;
    2)
        echo -e "${GREEN}切換到生產環境...${NC}"
        cd "$SCRIPT_DIR/production"
        echo -e "${YELLOW}當前位置: $(pwd)${NC}"
        echo -e "${YELLOW}可用指令:${NC}"
        echo "  - RUN_MODE=octane docker-compose up -d --build  # 重新建置並啟動"
        echo "  - docker-compose up -d                         # 啟動服務"
        echo "  - docker-compose logs -f                       # 查看日誌"
        echo "  - docker-compose exec php bash                 # 進入容器"
        exec bash
        ;;
    3)
        echo -e "${GREEN}啟動智能部署工具...${NC}"
        "$SCRIPT_DIR/smart-deploy.sh"
        ;;
    *)
        echo "無效選項"
        exit 1
        ;;
esac
