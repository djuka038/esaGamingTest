<?php

namespace App\Providers;

use App\Providers\GameService;
use App\Providers\ArmyService;
use App\Providers\CacheService;
use Illuminate\Database\Eloquent\Collection;
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

            foreach ($game->participants as $attackingArmy) {
                if (self::getRandomChanceForAttack($attackingArmy->numOfUnits) === true) {
                    $defender = null;
                    switch ($attackingArmy->strategy) {
                        case ArmyService::STRATEGY_STRONGEST:
                            $defender = GameService::getStrongestParticipantOfGame($game->id, $attackingArmy->id);
                            break;
                        case ArmyService::STRATEGY_WEAKEST:
                            $defender = GameService::getWeakestParticipantOfGame($game->id, $attackingArmy->id);
                            break;
                        case ArmyService::STRATEGY_RANDOM:
                            $defender = GameService::getRandomParticipantOfGame($game->id, $attackingArmy->id);
                            break;
                    }

                    if ($defender !== null) {
                        $turn = self::TURN_ATTACKER;
                        $defenderArmy = $defender->army;
                        CacheService::cacheData(self::ATTACKING_UNITS, self::mapUnits($attackingArmy->units->keyBy('id')));
                        CacheService::cacheData(self::DEFENDING_UNITS, self::mapUnits($defenderArmy->units->keyBy('id')));

                        $maxTurns = CacheService::getCachedDataByKey(self::ATTACKING_UNITS)->count()
                            + CacheService::getCachedDataByKey(self::DEFENDING_UNITS)->count();

                        for ($i = 1; $i <= $maxTurns; $i++) {
                            switch ($turn) {
                                case self::TURN_ATTACKER:
                                    if (CacheService::getCachedDataByKey(self::ATTACKING_UNITS)->count() > 0) {
                                        $attackingUnit = CacheService::getCachedDataByKey(self::ATTACKING_UNITS)
                                            ->where('attacked', false)->where('standDownTime', '<', microtime(true));
                                        if ($attackingUnit->isNotEmpty()) {
                                            self::attackUnit($attackingUnit->random(), $turn);
                                        }
                                    } else {
                                        GameService::updateParticipantStatusDead($game->id, $attackingArmy->id);
                                    }
                                    $turn = self::TURN_DEFENDER;
                                    break;
                                case self::TURN_DEFENDER:
                                    if (CacheService::getCachedDataByKey(self::DEFENDING_UNITS)->count() > 0) {
                                        $attackingUnit = CacheService::getCachedDataByKey(self::DEFENDING_UNITS)
                                            ->where('attacked', false)->where('standDownTime', '<', microtime(true));
                                        if ($attackingUnit->isNotEmpty()) {
                                            self::attackUnit($attackingUnit->random(), $turn);
                                        }
                                    } else {
                                        GameService::updateParticipantStatusDead($game->id, $defenderArmy->id);
                                    }
                                    $turn = self::TURN_ATTACKER;
                                    break;
                            }
                        }
                        CacheService::flushCache();
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

    /**
     * Engages two units
     *
     * @param stdClass $attackingUnit
     * @param stdClass $defendingUnit
     * @return void
     */
    private static function attackUnit(stdClass $attackingUnit, string $turn)
    {
        $attackingUnits = CacheService::getCachedDataByKey(self::ATTACKING_UNITS);
        $defendingUnits = CacheService::getCachedDataByKey(self::DEFENDING_UNITS);

        if ($defendingUnits->isNotEmpty() && $attackingUnits->isNotEmpty()) {
            $defendingUnit = null;

            if ($turn === self::TURN_ATTACKER) {
                $defendingUnit = $defendingUnits->where('id', $attackingUnit->attackedUnitId)->first();
                if ($defendingUnit === null) {
                    $defendingUnit = $defendingUnits->random();
                }
            } else {
                $defendingUnit = $attackingUnits->where('id', $attackingUnit->attackedUnitId)->first();
                if ($defendingUnit === null) {
                    $defendingUnit = $attackingUnits->random();
                }
            }

            if ($attackingUnit->attackedUnitId === null) {
                $attackingUnit->attackedUnitId = $defendingUnit->id;
                $attackingUnit->attacked = true;
            }

            if ($defendingUnit->attackedUnitId === null) {
                $defendingUnit->attackedUnitId = $attackingUnit->id;
            }

            if ($defendingUnits->where('health', '>', 0)->count() === 1) {
                $defendingUnit->health -= env('ATTACK_DAMAGE_ONE_UNIT');
                $defendingUnit->receivedDamage += env('ATTACK_DAMAGE_ONE_UNIT');
            } else {
                $defendingUnit->health -= env('ATTACK_DAMAGE');
                $defendingUnit->receivedDamage += env('ATTACK_DAMAGE');
            }

            if ($defendingUnit->receivedDamage >= env('STAND_DOWN_DAMAGE')) {
                $defendingUnit->standDownTime = microtime(true) + env('STAND_DOWN_TIME');
            }

            if ($turn === self::TURN_ATTACKER) {
                $attackingUnits[$attackingUnit->id] = $attackingUnit;
                CacheService::cacheData(self::ATTACKING_UNITS, $attackingUnits);

                if ($defendingUnit->health <= 0) {
                    $defendingUnits->pop($defendingUnit->id);
                } else {
                    $defendingUnits[$defendingUnit->id] = $defendingUnit;
                }
                CacheService::cacheData(self::DEFENDING_UNITS, $defendingUnits);
                ArmyService::updateUnitHealth($defendingUnit->id, $defendingUnit->health);
            } else {
                $defendingUnits[$attackingUnit->id] = $attackingUnit;
                CacheService::cacheData(self::DEFENDING_UNITS, $defendingUnits);

                if ($attackingUnit->health <= 0) {
                    $attackingUnits->pop($attackingUnit->id);
                } else {
                    $attackingUnits[$defendingUnit->id] = $defendingUnit;
                }
                CacheService::cacheData(self::ATTACKING_UNITS, $attackingUnits);
                ArmyService::updateUnitHealth($defendingUnit->id, $defendingUnit->health);
            }

            self::recoverUnits();
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

    private static function recoverUnits()
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
