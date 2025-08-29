#!/bin/bash 
set -e 

# 定義容器名稱或容器 ID（根據你自己的設置）
PHP_CONTAINER="wallet-php-fpm"

echo "=== 部署開始 ==="
echo "PHP 容器名稱: $PHP_CONTAINER"

# 檢查容器是否存在並運行
if ! docker ps | grep -q $PHP_CONTAINER; then
    echo "錯誤: 容器 $PHP_CONTAINER 未運行"
    echo "現有運行的容器:"
    docker ps
    exit 1
fi

echo "✓ 容器 $PHP_CONTAINER 運行中"

# 進入維護模式或傳回 true 
# 如果已經處於維護模式
echo "進入維護模式..."
(docker exec $PHP_CONTAINER php artisan down) || true 

# 拉取最新版本的應用程式
echo "拉取最新代碼..."
git pull origin master

# 安裝 Composer 依賴項
echo "安裝 Composer 依賴..."
docker exec $PHP_CONTAINER composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader 

# 清除舊快取
echo "清除舊快取..."
docker exec $PHP_CONTAINER php artisan clear-compiled 

# 重新建立快取
echo "重新建立快取..."
docker exec $PHP_CONTAINER php artisan optimize 

# 編譯 npm 資產
# 如果需要在 Docker 容器內執行 npm，你也可以在這裡使用 docker exec
# docker exec $PHP_CONTAINER npm run prod 

# 執行資料庫遷移
echo "執行資料庫遷移..."
docker exec $PHP_CONTAINER php artisan migrate --force 

# 退出維護模式
echo "退出維護模式..."
docker exec $PHP_CONTAINER php artisan up 

echo "重啟 PHP 容器..."
docker restart $PHP_CONTAINER

echo "=== 部署完成！ ==="
