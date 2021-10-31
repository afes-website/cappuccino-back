# CAPPUCCINO back-end

***C**ontrol **APP**lication for **U**nited **C**ontactless **C**heck-**IN** **O**peration*

![version](https://img.shields.io/badge/dynamic/json?color=007ec6&label=version&style=for-the-badge&query=version&url=https://raw.githubusercontent.com/afes-website/cappuccino-back/develop/composer.json)

[![@afes-website/docs](https://img.shields.io/badge/@afes--website/docs-v3.3.3-555.svg?logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxODAgMTgwIj48ZGVmcz48c3R5bGU+LmNscy0xe2ZpbGw6I2ZmZjt9PC9zdHlsZT48L2RlZnM+PHBhdGggY2xhc3M9ImNscy0xIiBkPSJNMTM0LjYsODkuNzIsMTU2LjksNTEuMUgxMTJMOTAsMTMsNjgsNTEuMUgyMy4xTDQ1LjQsODkuNzNsLTIyLjI5LDM4LjZINjcuNjhMOTAsMTY3bDIyLjMyLTM4LjY3SDE1Ni45Wm04LjUyLDI4LjM1TDk3LjQ5LDkxLjczaDMyLjQ1Wk0xMjkuOTMsODcuNzNIOTcuNDdsNDUuNjUtMjYuMzZaTTk1LjQ3LDg0LjI3LDExMS43LDU2LjE1bDI5LjQyLDEuNzVabTEyLjYtMjkuODRMOTIsODIuMjhWMzAuMDdaTTg4LDgyLjI1LDcxLjkyLDU0LjQyLDg4LDMwLjA2Wk02OC4zLDU2LjE1LDg0LjU0LDg0LjI2LDM4Ljg4LDU3LjkxWk0zNi44OCw2MS4zNyw4Mi41Myw4Ny43Mkg1MC4wNlpNNTAuMDcsOTEuNzJIODIuNTFMMzYuODksMTE4LjA3Wm0zNC40OSwzLjQ0TDY4LjMyLDEyMy4yOWwtMjkuNDMtMS43NlptLTEyLjgsMzAuMTdMODgsOTcuMjJ2NTIuNzFaTTkyLDk3LjIybDE2LjI0LDI4LjEyTDkyLDE0OS45NFptMTkuNjgsMjYuMDdMOTUuNDIsOTUuMTZsNDUuNywyNi4zN1oiLz48L3N2Zz4K&logoColor=fff&style=flat-square&labelColor=457fb3)](https://github.com/afes-website/docs/tree/v3.3.3)
![Lumen](https://img.shields.io/badge/dynamic/json?color=555&label=Lumen&style=flat-square&query=require["laravel/lumen-framework"]&url=https://raw.githubusercontent.com/afes-website/cappuccino-back/develop/composer.json&labelColor=E74430&logo=lumen&logoColor=fff)
![PHP](https://img.shields.io/badge/dynamic/json?color=555&label=PHP&style=flat-square&query=require["php"]&url=https://raw.githubusercontent.com/afes-website/cappuccino-back/develop/composer.json&labelColor=777BB4&logo=php&logoColor=fff)

## Project setup

1. copy `.env.example` to `.env`
2. edit `.env`
3. run commands on below
    ```sh
    composer install
    php artisan migrage
    php artisan db:seed
    ```
4. publish `public/` as document root

## Run test (PHPUnit)

```sh
vendor/bin/phpunit
```
