<?php
use Laravel\Lumen\Routing\Router;

/** @var Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post(
    '/auth/login',
    ['uses'=>'AuthController@authenticate','middleware'=>'throttle:5,1']
); // throttled 5 requests/1 min

$router->get('/auth/user', ['uses'=>'AuthController@userInfo', 'middleware'=>'auth']);
$router->post('/auth/change_password', ['uses'=>'AuthController@changePassword', 'middleware'=>'auth']);
