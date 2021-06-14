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
    ['uses'=>'AuthController@authenticate','middleware'=>'throttle:5, 1']
); // throttled 5 requests/1 min

$router->get('/auth/user', ['uses'=>'AuthController@userInfo', 'middleware'=>'auth']);
$router->post('/auth/change_password', ['uses'=>'AuthController@changePassword', 'middleware'=>'auth']);


$router->group(['prefix' => 'reservations'], function () use ($router) {
    $router->post('/', ['uses' => 'ReservationController@create']);
    $router->get('search', ['uses' => 'ReservationController@index', 'middleware' => 'auth:reservation']);
    $router->get('{id}', ['uses' => 'ReservationController@show', 'middleware' => 'auth:reservation']);
    $router->get(
        '{id}/check',
        ['uses' => 'ReservationController@check', 'middleware' => 'auth:reservation, executive']
    );
});

$router->group(['prefix' => 'guests'], function () use ($router) {
    $router->get('/', ['uses' => 'GuestController@index', 'middleware' => 'auth:executive']);
    $router->post('check-in', ['uses' => 'GuestController@checkIn', 'middleware' => 'auth:executive']);
    $router->get('{id}', ['uses' => 'GuestController@show', 'middleware' => 'auth:executive']);
    $router->post('{id}/check-out', ['uses' => 'GuestController@checkOut', 'middleware' => 'auth:executive']);
    $router->post('{id}/enter', ['uses' => 'GuestController@enter', 'middleware' => 'auth:exhibition, admin']);
    $router->post('{id}/exit', ['uses' => 'GuestController@exit', 'middleware' => 'auth:exhibition, admin']);
});

$router->get('terms', ['uses' => 'TermController@index', 'middleware' => 'auth:executive, exhibition']);

$router->get(
    'log',
    ['uses' => 'ActivityLogController@index', 'middleware' => 'auth:executive, exhibition, reservation']
);


$router->group(['prefix' => 'exhibitions'], function () use ($router) {
    $router->get('{id}', ['uses' => 'ExhibitionController@show', 'middleware' => 'auth:exhibition']);
    $router->get('/', ['uses' => 'ExhibitionController@index', 'middleware' => 'auth:exhibition, executive']);
});

$router->options('{path:.*}', function () {
}); // any path
