<?php

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
    return view('index');
});

$router->group(['prefix' => 'v1/'], function ($router) {
    $router->post('addArmy', 'ArmyController@addArmy');
    $router->get('armies', 'ArmyController@getArmies');

    $router->post('createGame', 'GameController@createGame');
    $router->post('addParticipant', 'GameController@addParticipant');
    $router->get('game/{gameId}', 'GameController@getGameById');
    $router->get('games', 'GameController@getGames');
    $router->get('gameStatus/{gameId}', 'GameController@getGameStatus');
    $router->get('gameParticipants', 'GameController@getParticipants');

    $router->get('run', 'BattleController@run');
    $router->get('gameStats', 'BattleController@gameStats');
});
