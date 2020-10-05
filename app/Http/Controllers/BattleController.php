<?php

namespace App\Http\Controllers;

use App\Providers\BattleService;
use App\Providers\GameService;

class BattleController extends Controller
{

    public function run()
    {
        return response()->json(
            BattleService::run()
        );
    }

    public function gameStats()
    {
        return response()->json(
            GameService::getStatusActiveGames()
        );
    }
}
