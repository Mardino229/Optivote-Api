/root/.config/herd-lite/bin/composer install
php artisan key:generate
php artisan db:seed
php artisan cache:clear
php artisan migrate
php artisan scribe:generate
