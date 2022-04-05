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
    ['uses'=>'AuthController@authenticate', 'middleware'=>'throttle:5, 1']
); // throttled 5 requests/1 min
$router->get('/auth/me', ['uses'=>'AuthController@currentUserInfo', 'middleware'=>'auth']);
$router->get('/auth/users', ['uses'=>'AuthController@all', 'middleware'=>'auth:admin']);
$router->get('/auth/users/{id}', ['uses'=>'AuthController@show', 'middleware'=>'auth']);
$router->post('/auth/users/{id}/change_password', ['uses'=>'AuthController@changePassword', 'middleware'=>'auth']);
$router->post('/auth/users/{id}/regenerate', ['uses'=>'AuthController@regenerate', 'middleware'=>'auth:admin']);

$router->group(['prefix' => 'exhibitions'], function () use ($router) {
    $router->get('/', ['uses' => 'ExhibitionController@index']);
    $router->get('{id}', ['uses' => 'ExhibitionController@show']);
});

$router->group(['prefix' => 'guests'], function () use ($router) {
    $router->get('/', ['uses' => 'GuestController@index', 'middleware' => 'auth:executive']);
    $router->post(
        'bulk-update',
        ['uses' => 'BulkUpdateController@post', 'middleware' => 'auth:executive, exhibition']
    );
    $router->post('check-in', ['uses' => 'GuestController@checkIn', 'middleware' => 'auth:executive']);
    $router->post('register-spare', ['uses' => 'GuestController@registerSpare', 'middleware' => 'auth:executive']);
    $router->get('{id}', ['uses' => 'GuestController@show', 'middleware' => 'auth:executive']);
    $router->post('{id}/check-out', ['uses' => 'GuestController@checkOut', 'middleware' => 'auth:executive']);
    $router->post('{id}/enter', ['uses' => 'GuestController@enter', 'middleware' => 'auth:exhibition, admin']);
    $router->post('{id}/exit', ['uses' => 'GuestController@exit', 'middleware' => 'auth:exhibition, admin']);
});

$router->get('/images/{id}', ['uses' => 'ImageController@show']);
$router->post('/images', ['uses' => 'ImageController@create', 'middleware' => 'auth']);

$router->get(
    'log',
    ['uses' => 'ActivityLogController@index', 'middleware' => 'auth:executive, exhibition, reservation']
);

$router->group(['prefix' => 'reservations'], function () use ($router) {
    $router->get('search', ['uses' => 'ReservationController@search', 'middleware' => 'auth:reservation']);
    $router->get('{id}', ['uses' => 'ReservationController@show', 'middleware' => 'throttle:5, 1']);
    $router->get('{id}/check', [
        'uses' => 'ReservationController@check',
        'middleware' => 'auth:reservation, executive'
    ]);
});

$router->get('terms', ['uses' => 'TermController@index', 'middleware' => 'auth:executive, exhibition']);

$router->options('{path:.*}', function () {
}); // any path
