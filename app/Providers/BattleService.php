<?php

namespace App\Providers;

use App\Providers\GameService;
use App\Providers\ArmyService;
use App\Providers\CacheService;
use Illuminate\Support\Collection;
use stdClass;

class BattleService
{

    const TURN_ATTACKER = 'attacker';
    const TURN_DEFENDER = 'defender';
    const ATTACKING_UNITS = 'attackingUnits';
    const DEFENDING_UNITS = 'defendingUnits';

    public static function run()
    {
        foreach (GameService::getReadyGames() as $game) {
            if (GameService::getGameStatus($game->id) === GameService::STATUS_OPEN) {
                GameService::updateGame($game->id, ['status' => GameService::STATUS_ACTIVE]);
            }

            foreach ($game->participants as $attacker) {
                $defender = null;
                switch ($attacker->strategy) {
                    case ArmyService::STRATEGY_STRONGEST:
                        $defender = GameService::getStrongestParticipantOfGame($game->id, $attacker->id);
                        break;
                    case ArmyService::STRATEGY_WEAKEST:
                        $defender = GameService::getWeakestParticipantOfGame($game->id, $attacker->id);
                        break;
                    case ArmyService::STRATEGY_RANDOM:
                        $defender = GameService::getRandomParticipantOfGame($game->id, $attacker->id);
                        break;
                }

                if ($defender !== null) {
                    $turn = self::TURN_ATTACKER;
                    $attackingArmy =  self::mapUnits($attacker->units->keyBy('id'));
                    $defendingArmy =  self::mapUnits($defender->army->units->keyBy('id'));

                    for ($i = 1; $i <= $attackingArmy->count() + $defendingArmy->count(); $i++) {
                        if ($attackingArmy->count() < 1) {
                            GameService::updateParticipantStatusDead($game->id, $attacker->id);
                        }

                        if ($defendingArmy->count() < 1) {
                            GameService::updateParticipantStatusDead($game->id, $defender->id);
                        }

                        if ($attackingArmy->count() > 0 && $defendingArmy->count() > 0) {
                            switch ($turn) {
                                case self::TURN_ATTACKER:
                                    if (self::getRandomChanceForAttack($attackingArmy->count())) {
                                        self::engageArmies($attackingArmy, $defendingArmy);
                                    }
                                    $turn = self::TURN_DEFENDER;
                                    break;
                                case self::TURN_DEFENDER:
                                    if (self::getRandomChanceForAttack($defendingArmy->count())) {
                                        self::engageArmies($defendingArmy, $attackingArmy);
                                    }
                                    $turn = self::TURN_ATTACKER;
                                    break;
                            }
                        }
                    }
                }
            }
            if (GameService::getNumAliveParticipantsByGame($game->id) < 2) {
                GameService::updateGame($game->id, ['status' => GameService::STATUS_FINISHED]);
            }
        }
    }

    /**
     * Maps units
     *
     * @param Collection $units
     * @return Collection
     */
    private static function mapUnits(Collection $units)
    {
        return $units->map(function ($items) {
            if ($items->health > 0) {
                $dataObject = new stdClass();
                $dataObject->id = $items->id;
                $dataObject->armyId = $items->army_id;
                $dataObject->health = $items->health;
                $dataObject->receivedDamage = 0;
                $dataObject->attackedUnitId = null;
                $dataObject->attacked = false;
                $dataObject->standDownTime = null;
                return $dataObject;
            }
        });
    }

    private static function engageArmies(Collection $attackingArmy, Collection $defendingArmy)
    {
        $attackingUnit = null;
        $defendingUnit = null;

        if ($attackingArmy->where('attacked', false)->where('standDownTime', '<', microtime(true))->count() > 0) {
            $attackingUnit = $attackingArmy->where('attacked', false)->where('standDownTime', '<', microtime(true))
                ->random();

            $defendingUnit = $defendingArmy->where('id', $attackingUnit->attackedUnitId)->first();
        }

        if (!$defendingUnit instanceof stdClass) {
            $defendingUnit = $defendingArmy->random();
        }

        if ($attackingUnit instanceof stdClass && $defendingUnit instanceof stdClass) {
            $defendingUnit = self::engageUnits($attackingUnit, $defendingUnit, $defendingArmy->count() === 1);

            if ($attackingUnit->attackedUnitId === null) {
                $attackingArmy[$attackingUnit->id]->attackedUnitId = $defendingUnit->id;
                $attackingArmy[$attackingUnit->id]->attacked = true;
            }

            if ($defendingUnit->health <= 0) {
                $defendingArmy->pop($defendingUnit->id);
            } else {
                $defendingArmy[$defendingUnit->id]->health = $defendingUnit->health;
                $defendingArmy[$defendingUnit->id]->receivedDamage = $defendingUnit->receivedDamage;
                $defendingArmy[$defendingUnit->id]->standDownTime = $defendingUnit->standDownTime;

                if ($defendingUnit->attackedUnitId === null) {
                    $defendingArmy[$defendingUnit->id]->attackedUnitId = $attackingUnit->id;
                }
            }

            self::recoverUnits($attackingArmy, $defendingArmy);
        }
    }

    private static function engageUnits(stdClass $attackingUnit, stdClass $defendingUnit, bool $lastUnit = false)
    {
        if ($lastUnit) {
            $defendingUnit->health -= env('ATTACK_DAMAGE_ONE_UNIT');
            $defendingUnit->receivedDamage += env('ATTACK_DAMAGE_ONE_UNIT');
        } else {
            $defendingUnit->health -= env('ATTACK_DAMAGE');
            $defendingUnit->receivedDamage += env('ATTACK_DAMAGE');
        }

        if ($defendingUnit->receivedDamage >= env('STAND_DOWN_DAMAGE')) {
            $defendingUnit->standDownTime = microtime(true) + env('STAND_DOWN_TIME');
        }

        ArmyService::updateUnitHealth($defendingUnit->id, $defendingUnit->health);

        return $defendingUnit;
    }

    private static function recoverUnits(Collection $attackingArmy, Collection $defendingArmy)
    {
        $attackingUnitsRecovery = $attackingArmy->where('receivedDamage', '>=', env('STAND_DOWN_DAMAGE'))
            ->where('standDownTime', '<', microtime(true));
        $defendingUnitsRecovery = $defendingArmy->where('receivedDamage', '>=', env('STAND_DOWN_DAMAGE'))
            ->where('standDownTime', '<', microtime(true));

        if ($attackingUnitsRecovery->isNotEmpty()) {
            foreach ($attackingUnitsRecovery as $unit) {
                $attackingArmy[$unit->id]->receivedDamage = 0;
                $attackingArmy[$unit->id]->standDownTime = null;
            }
        }

        if ($defendingUnitsRecovery->isNotEmpty()) {
            foreach ($defendingUnitsRecovery as $unit) {
                $defendingArmy[$unit->id]->receivedDamage = 0;
                $defendingArmy[$unit->id]->standDownTime = null;
            }
        }
    }

    /**
     * Returns true or false if attacker has chances
     *
     * @param integer $numOfUnits
     * @return bool
     */
    private static function getRandomChanceForAttack(int $numOfUnits)
    {
        return rand(1, env('MAX_UNITS')) <= $numOfUnits ? true : false;
    }

    private static function recoverUnits1()
    {
        $attackingUnits = CacheService::getCachedDataByKey(self::ATTACKING_UNITS);
        $defendingUnits = CacheService::getCachedDataByKey(self::DEFENDING_UNITS);

        $attackingUnitsRecovery = $attackingUnits->where('receivedDamage', '>=', env('STAND_DOWN_DAMAGE'))
            ->where('standDownTime', '<', microtime(true));
        $defendingUnitsRecovery = $defendingUnits->where('receivedDamage', '>=', env('STAND_DOWN_DAMAGE'))
            ->where('standDownTime', '<', microtime(true));

        if ($attackingUnitsRecovery->isNotEmpty()) {
            foreach ($attackingUnitsRecovery as $unit) {
                $unit->receivedDamage = 0;
                $unit->standDownTime = null;
                $attackingUnits[$unit->id] = $unit;
                CacheService::cacheData(self::ATTACKING_UNITS, $attackingUnits);
            }
        }

        if ($defendingUnitsRecovery->isNotEmpty()) {
            foreach ($defendingUnitsRecovery as $unit) {
                $unit->receivedDamage = 0;
                $unit->standDownTime = null;
                $defendingUnits[$unit->id] = $unit;
                CacheService::cacheData(self::DEFENDING_UNITS, $defendingUnits);
            }
        }
    }
}
