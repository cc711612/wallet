#!/bin/bash
set -e

echo "=== 啟動 entrypoint.sh (Production) ==="

# 設定環境變數檔案
echo "設定環境變數..."
printenv | sed 's/^\(.*\)$/export "\1"/g' > "/.schedule-env.sh" && chmod +x "/.schedule-env.sh" &

# 設定儲存目錄權限
if [ -d "/var/www/html/storage" ]; then 
    echo "設定 storage 目錄權限..."
    chmod -R 755 "/var/www/html/storage"
fi &

if [ -d "/var/www/html/storage/framework/cache" ]; then 
    echo "設定 cache 目錄權限..."
    chmod -R 755 "/var/www/html/storage/framework/cache"
fi &

if [ -d "/var/www/html/storage/framework/cache/data" ]; then 
    echo "設定 cache data 目錄權限..."
    chmod -R 755 "/var/www/html/storage/framework/cache/data"
fi &

# 確保 supervisor 日誌目錄存在
if [ ! -d "/var/log/supervisor" ]; then 
    echo "建立 supervisor 日誌目錄..."
    mkdir -p "/var/log/supervisor"
fi &

# 安裝 composer 依賴
echo "安裝 composer 依賴..."
composer install --no-dev --optimize-autoloader &

# 設定 crontab 權限
if [ -d "/etc/crontabs" ]; then
    echo "設定 crontab 權限..."
    chown root /etc/crontabs/* 2>/dev/null || true
fi &

# 啟動服務
echo "啟動 supervisord..."
supervisord &

echo "啟動 crond..."
crond &

echo "啟動 php-fpm..."
docker-php-entrypoint php-fpm