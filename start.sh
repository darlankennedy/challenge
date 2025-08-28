composer install

php artisan migrate --seed

php artisan key:generate --force

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear



php-fpm
