<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Providers\ArmyService;

class ArmyController extends Controller
{

    public function addArmy(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|string|unique:armies',
                'units' => 'required|numeric|min:' . env('MIN_UNITS') . '|max:' . env('MAX_UNITS'),
                'strategy' => 'required|string|in:random,weakest,strongest'
            ]
        );

        return response()->json(
            ArmyService::addArmy($request->name, $request->units, $request->strategy)
        );
    }

    public function getArmies()
    {
        return response()->json(
            ArmyService::getArmies()
        );
    }
}
