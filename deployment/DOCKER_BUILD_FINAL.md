# 極速 Docker Build 最終版本

## 版本資訊
- **建立日期**: 2024-08-30
- **版本**: Ultra-fast Build (極速版)
- **效能提升**: 75% (從 20+ 分鐘縮短至 ~5 分鐘)
- **映像大小**: 242MB

## 核心優化策略

### 1. 單一 RUN 命令
將所有系統依賴、PHP 擴展和 PECL 安裝合併為一個 RUN 命令，減少 Docker layers 並最大化快取效率。

### 2. 虛擬依賴管理
使用 `--virtual .build-deps` 標記編譯依賴，完成後統一清理，減少最終映像大小。

### 3. 並行編譯優化
使用 `-j$(nproc)` 參數進行並行編譯，充分利用多核心處理器。

### 4. 快取友好架構
- 優先安裝變動較少的系統依賴
- 最後處理應用程式碼複製
- 精確的 .dockerignore 設定

## 技術堆棧
- **Base Image**: php:8.2-fpm-alpine
- **PHP Version**: 8.2.29
- **Laravel**: 9.52.20
- **Swoole**: 6.0.2
- **Runtime**: Octane

## 構建指令
```bash
# 開發環境
cd deployment/develop
docker build -f Dockerfile.php82 -t wallet-app:dev .

# 生產環境
cd deployment/production
docker build -f Dockerfile.php82 -t wallet-app:prod .
```

## 部署狀態
✅ 開發環境已部署並測試  
✅ 生產環境已同步更新  
✅ API 服務正常運行  
✅ 所有優化檔案已清理完成  

---
**定版完成** - 此版本為正式生產版本，其他實驗性變體已移除。
