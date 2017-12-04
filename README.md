# laravel55-restapi

This is a demo for Laravel 5.5 REST api. 

For frontend example, im using [vue2-adminlte](https://github.com/chrissetyawan/vue2-adminlte/) 

## FEATURE

```

- Login, Register with email confirmation, Forgot password, Change password
- Email notification to user
- CRUD example, User Management
- Role based restriction, jeremykenedy/laravel-roles
- JWT-Auth - tymon/jwt-auth
- Dingo API - dingo/api
- Laravel-CORS barryvdh/laravel-cors

```


## USAGE

```
$ composer install
$ cp .env.example .env
$ set .env
$ php artisan migrate:refresh --seed
$ php artisan serve (to run the website)


```
## REST API DESIGN

just a demo for rest api design

```
    demo： user, post
    
    post   /api/auth/register              	 register a new user
    post   /api/auth/login              	 login
    put    /api/auth/authorizations/current   refresh token
    delete /api/auth/logout            	 logout
    
    post   /api/user              	 create a user
    get    /api/user/5            	 user detail
    put    /api/user/5            	 update a user
    delete /api/user/5            	 delete a user
```

## Info
Base template from [francescomalatesta/laravel-api-boilerplate-jwt](https://github.com/francescomalatesta/laravel-api-boilerplate-jwt) 
