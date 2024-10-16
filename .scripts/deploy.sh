#!/bin/bash 
set -e 

echo  "部署開始..." 

# 進入維護模式或傳回 true 
# 如果已經處於維護模式
(php artisan down) || true 

# 拉取最新版本的應用程式
git pull origin production 

# 安裝 Composer 依賴項
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader 

# 清除舊快取
php artisan clear-compiled 

#重新建立快取
php artisan optimize 

# 編譯 npm 資產
# npm run prod 

# 執行資料庫遷移
php artisan migrate --force 

# 退出維護模式
php artisan up 

echo  "部署完成！"