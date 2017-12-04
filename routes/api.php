<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {

    //auth
    $api->group(['prefix' => 'auth'], function(Router $api) {
        $api->post('register', 'App\\Api\\V1\\Controllers\\AuthController@register');
        $api->put('emailconfirmation/{token}', 'App\\Api\\V1\\Controllers\\AuthController@emailconfirmation');

        $api->post('login', 'App\\Api\\V1\\Controllers\\AuthController@login');
        $api->post('sendresetemail', 'App\\Api\\V1\\Controllers\\AuthController@sendResetEmail');
        $api->post('resetpassword', 'App\\Api\\V1\\Controllers\\AuthController@resetPassword');
    });

    $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
        //profile
        $api->get('profile/{id}', 'App\\Api\\V1\\Controllers\\ProfileController@show');
        $api->put('profile/{id}', 'App\\Api\\V1\\Controllers\\ProfileController@update');

        //auth
        $api->group(['prefix' => 'auth'], function(Router $api) {
            $api->post('refresh', 'App\\Api\\V1\\Controllers\\AuthController@refresh');
            $api->delete('logout', 'App\\Api\\V1\\Controllers\\AuthController@logout');
            $api->get('me', 'App\\Api\\V1\\Controllers\\AuthController@me');
            $api->put('activate/{id}', 'App\\Api\\V1\\Controllers\\AuthController@activate');
        });

        $api->group(['middleware' => ['role:admin|superadmin']], function(Router $api) {
            //user management
            $api->get('user', 'App\\Api\\V1\\Controllers\\UserController@index');
            $api->post('user', 'App\\Api\\V1\\Controllers\\UserController@store');
            $api->get('user/{idfilter}', 'App\\Api\\V1\\Controllers\\UserController@show');
            $api->put('user/{id}', 'App\\Api\\V1\\Controllers\\UserController@update');
            $api->delete('user/{id}', 'App\\Api\\V1\\Controllers\\UserController@destroy');

        });
    });

    $api->get('/', function() {
        return response()->json([
            'message' => 'API version 1.0.0'
        ]);
    });
});
