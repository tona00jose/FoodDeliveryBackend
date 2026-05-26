<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# About Food Delivery

This app is to write a food delivery service API.

## Install

```bash
php -v
	PHP 8.x.x
```

```bash
composer install
copy .env.example .env
php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=food_delivery
DB_USERNAME=root
DB_PASSWORD=

php artisan migrate
php artisan db:seed

npm install
php artisan serve
```
## First Login
```bash
email super_admin@gmail.com
password super_admin
```


## Postman Test

Food Delivery.postman_collection.json

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
