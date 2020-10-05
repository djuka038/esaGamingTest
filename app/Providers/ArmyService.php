<?php

namespace App\Providers;

use App\Models\Army;
use App\Models\Unit;
use Illuminate\Support\Collection;

class ArmyService
{

    const STRATEGY_STRONGEST = 'strongest';
    const STRATEGY_WEAKEST = 'weakest';
    const STRATEGY_RANDOM = 'random';

    /**
     *
     * CREATE
     *
     */

    /**
     * Adds army
     *
     * @param string $name
     * @param integer $units
     * @param string $strategy
     * @return Army returns army object
     */
    private static function createArmy(string $name, int $units, string $strategy)
    {
        return Army::create([
            'name' => $name,
            'strategy' => $strategy
        ]);
    }

    /**
     * Adds units to army
     *
     * @param integer $armyId
     * @param integer $numOfUnits
     * @return void
     */
    private static function addUnits(int $armyId, int $numOfUnits)
    {
        $units = [];
        for ($i = 0; $i < $numOfUnits; $i++) {
            $units[] = [
                'army_id' => $armyId,
                'health' => env('MAX_UNIT_HEALTH'),
            ];
        }

        Unit::insert($units);
    }

    /**
     * Adds army with units
     *
     * @param string $name
     * @param integer $units
     * @param string $strategy
     * @return Army
     */
    public static function addArmy(string $name, int $units, string $strategy)
    {
        $army = self::createArmy($name, $units, $strategy);
        self::addUnits($army->id, $units);
        return $army;
    }

    /**
     *
     * READ
     *
     */

    /**
     * Gets all armies
     *
     * @return Collection
     */
    public static function getArmies()
    {
        return Army::get();
    }

    /**
     *
     * UPDATE
     *
     */

    /**
     * Updates unit health
     *
     * @param integer $unitId
     * @param int $health
     * @return bool
     */
    public static function updateUnitHealth(int $unitId, int $health)
    {
        if ($health < 0) $health = 0;
        return boolval(Unit::where('id', $unitId)->update(['health' => $health]));
    }
}
