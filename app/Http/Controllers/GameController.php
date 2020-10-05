<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Providers\GameService;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{

    public function createGame()
    {
        return response()->json(
            GameService::createGame()
        );
    }

    public function addParticipant(Request $request)
    {
        $this->validate($request, [
            'game_id' => 'required|numeric|exists:games,id',
            'participant_id' => 'required|numeric|exists:armies,id',
        ]);

        $validator = Validator::make(
            [
                'status' => GameService::getGameStatus($request->game_id),
                'in_lobby' => GameService::checkArmyIsNotInTheGame($request->game_id, $request->participant_id)
            ],
            [
                'status' => 'in:open,active',
                'in_lobby' => 'accepted'
            ],
            [
                'status.in' => 'game is finished',
                'in_lobby.accepted' => 'the army is already in the game'
            ]
        );

        if ($validator->fails()) {
            return $validator->errors();
        }

        return response()->json(
            GameService::addParticipant($request->game_id, $request->participant_id)
        );
    }

    public function getGameById(int $gameId)
    {
        return response()->json(
            GameService::getGameById($gameId)
        );
    }

    public function getGameStatus(int $gameId)
    {
        return response()->json(
            GameService::getGameStatus($gameId)
        );
    }

    public function getGames()
    {
        return response()->json(
            GameService::getGames()
        );
    }

    public function getParticipants() {
        return response()->json(
            GameService::getParticipants()
        );
    }
}
