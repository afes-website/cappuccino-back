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


$router->group(['prefix' => 'reservations'], function () use ($router) {
    $router->post('/', ['uses' => 'ReservationController@create']);
    $router->get('search', ['uses' => 'ReservationController@index', 'middleware' => 'auth:reservation']);
    $router->get('{id}', ['uses' => 'ReservationController@show', 'middleware' => 'auth:reservation']);
    $router->get('{id}/check', ['uses' => 'ReservationController@check', 'middleware' => 'auth:reservation,executive']);
});

$router->group(['middleware' => 'auth:executive, exhibition'], function () use ($router) {
    $router->group(['prefix' => 'guests'], function () use ($router) {
        $router->post('check-in', ['uses' => 'GuestController@enter']);
        $router->get('{id}', ['uses' => 'GuestController@show']);
        $router->post('{id}/check-out', ['uses' => 'GuestController@exit']);
        $router->post('{id}/enter', ['uses' => 'ExhibitionController@enter', 'middleware' => 'auth:exhibition']);
        $router->post('{id}/exit', ['uses' => 'ExhibitionController@exit', 'middleware' => 'auth:exhibition']);
    });

    $router->get('terms', ['uses' => 'TermController@index', 'middleware' => 'auth:executive,exhibition']);

    $router->get(
        'logs',
        ['uses' => 'ActivityLogController@index', 'middleware' => 'auth:executive,exhibition,reservation']
    );


    $router->group(['prefix' => 'exhibitions'], function () use ($router) {
        $router->get('{id}', ['uses' => 'ExhibitionController@show', 'middleware' => 'auth:exhibition']);
        $router->get('/', ['uses' => 'ExhibitionController@index', 'middleware' => 'auth:exhibition,executive']);
    });
});

$router->options('{path:.*}', function () {
}); // any path
