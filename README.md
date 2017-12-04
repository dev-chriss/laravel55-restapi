# laravel55-restapi

This is a demo for Laravel 5.5 REST api. 

For frontend example, im using [vue2-adminlte](https://github.com/chrissetyawan/vue2-adminlte/) 

## FEATURE

```

- Login, Register with email confirmation, Forgot password, Change password
- Email and notification example
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
    post   /api/auth/register                       register a new user
    post   /api/auth/login                          login
    delete /api/auth/logout            	            logout
    put    /api/auth/emailconfirmation/{token}      email confirmation after registration
    post   /api/auth/sendresetemail                 send reset link if forgot password
    post   /api/auth/resetpassword            	    reset password after clicked reset link
    put    /api/auth/activate/{id}                  user activation
    get    /api/auth/me                             get auth user
    put    /api/auth/refresh                        refresh token
    
    get    /api/user              	                get all users
    post   /api/user              	                create a user
    get    /api/user/{idfilter}                     view user with specific criteria
    put    /api/user/{id}            	            update a user
    delete /api/user/{id}           	            delete a user
```

## Info
Base template from [francescomalatesta/laravel-api-boilerplate-jwt] (https://github.com/francescomalatesta/laravel-api-boilerplate-jwt) 
