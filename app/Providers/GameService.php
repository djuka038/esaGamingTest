<?php

namespace App\Providers;

use App\Models\Game;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;

class GameService
{

    const STATUS_OPEN = 'open';
    const STATUS_ACTIVE = 'active';
    const STATUS_FINISHED = 'finished';
    const PARTICIPANT_STATUS_ALIVE = 'alive';
    const PARTICIPANT_STATUS_DEAD = 'dead';

    /**
     *
     * CREATE
     *
     */

    /**
     * Creates game
     *
     * @return Game
     */
    public static function createGame()
    {
        return Game::create();
    }

    private static function createParticipant(int $gameId, int $participantId, int $order)
    {
        return Participant::create([
            'game_id' => $gameId,
            'participant_id' => $participantId,
            'order_of_play' => $order
        ]);
    }

    /**
     * Adds participant to game
     *
     * @param integer $gameId
     * @param integer $participantId
     * @return Participant
     */
    public static function addParticipant(int $gameId, int $participantId)
    {
        $order = 1;
        if (self::getGameStatus($gameId) === self::STATUS_OPEN) {
            $order = self::getMaxOrderOfGame($gameId) + 1;
        } else {
            self::reorderOtherParticipants($gameId);
        }

        return self::createParticipant($gameId, $participantId, $order);
    }

    /**
     *
     * READ
     *
     */

    /**
     * Gets maximum order of game
     *
     * @param integer $gameId
     * @return int
     */
    private static function getMaxOrderOfGame(int $gameId)
    {
        return Participant::where('game_id', $gameId)->max('order_of_play');
    }

    /**
     * Gets whole game object
     *
     * @param integer $gameId
     * @return Game
     */
    public static function getGameById(int $gameId)
    {
        return Game::with('participants')->find($gameId);
    }

    /**
     * Gets game status
     *
     * @param integer $gameId
     * @return string status
     */
    public static function getGameStatus(int $gameId)
    {
        return Game::find($gameId)->status;
    }

    /**
     * Gets all active games
     *
     * @return Collection
     */
    public static function getStatusActiveGames()
    {
        return DB::table('games as g')->select(DB::raw("g.id, a.name, gp.status, count('u.id') as 'units'"))
            ->join('game_participants as gp', 'g.id', '=', 'gp.game_id')
            ->join('armies as a', 'gp.participant_id', '=', 'a.id')
            ->join('units as u', 'a.id', '=', 'u.army_id')
            ->where('g.status', self::STATUS_ACTIVE)
            ->where('gp.status', self::PARTICIPANT_STATUS_ALIVE)
            ->where('u.health', '>', 0)
            ->groupByRaw('g.id, a.id')
            ->orderByRaw('gp.status asc, units desc')
            ->get();
    }

    /**
     * Gets number of alive participants for game
     *
     * @param integer $gameId
     * @return int
     */
    public static function getNumAliveParticipantsByGame(int $gameId)
    {
        return Participant::where('game_id', $gameId)->where('status', self::PARTICIPANT_STATUS_ALIVE)->count();
    }

    /**
     * Checks if army is in the game
     *
     * @param integer $gameId
     * @param integer $participantId
     * @return bool
     */
    public static function checkArmyIsNotInTheGame(int $gameId, int $participantId)
    {
        return Participant::where([
            'game_id' => $gameId,
            'participant_id' => $participantId
        ])->doesntExist();
    }

    /**
     * Gets ready games
     *
     * @return Collection
     */
    public static function getReadyGames()
    {
        return Game::from('games as g')->select(DB::raw("g.*, count(gp.id) as 'armies'"))
            ->join('game_participants as gp', 'g.id', '=', 'gp.game_id')
            ->where('gp.status', self::PARTICIPANT_STATUS_ALIVE)
            ->groupBy('g.id')
            ->havingRaw('count(armies) >= ? and g.status = ?', [env('MIN_GAME_PARTICIPANTS'), self::STATUS_OPEN])
            ->orHavingRaw('count(armies) >= 2 and g.status = ?', [self::STATUS_ACTIVE])
            ->limit(env('MAX_ACTIVE_BATTLES'))->get();
    }

    /**
     * Gets strongest participant of game
     *
     * @param integer $gameId
     * @param integer $excludeAttackerId exclude attacker
     * @return Participant
     */
    public static function getStrongestParticipantOfGame(int $gameId, int $excludeAttackerId)
    {
        return Participant::from('game_participants as p')->select(DB::raw("p.*, count(u.id) as 'units'"))
            ->join('armies as a', 'p.participant_id', '=', 'a.id')
            ->join('units as u', 'a.id', '=', 'u.army_id')
            ->where('p.game_id', $gameId)->where('p.participant_id', '<>', $excludeAttackerId)
            ->where('p.status', self::PARTICIPANT_STATUS_ALIVE)->where('u.health', '>', 0)
            ->groupBy('p.id')->orderBy('units', 'DESC')->first();
    }

    /**
     * Gets weakest participant of game
     *
     * @param integer $gameId
     * @param integer $excludeAttackerId exclude attacker
     * @return Participant
     */
    public static function getWeakestParticipantOfGame(int $gameId, int $excludeAttackerId)
    {
        return Participant::from('game_participants as p')->select(DB::raw("p.*, count(u.id) as 'units'"))
            ->join('armies as a', 'p.participant_id', '=', 'a.id')
            ->join('units as u', 'a.id', '=', 'u.army_id')
            ->where('p.game_id', $gameId)->where('p.participant_id', '<>', $excludeAttackerId)
            ->where('p.status', self::PARTICIPANT_STATUS_ALIVE)->where('u.health', '>', 0)
            ->groupBy('p.id')->orderBy('units', 'ASC')->first();
    }

    /**
     * Gets random participant of game
     * @param integer $gameId
     * @param integer $excludeAttackerId exclude attacker
     * @return Participant
     */
    public static function getRandomParticipantOfGame(int $gameId, int $excludeAttackerId)
    {
        return Participant::from('game_participants as p')->select(DB::raw("p.*, count(u.id) as 'units'"))
            ->join('armies as a', 'p.participant_id', '=', 'a.id')
            ->join('units as u', 'a.id', '=', 'u.army_id')
            ->where('p.game_id', $gameId)->where('p.participant_id', '<>', $excludeAttackerId)
            ->where('p.status', self::PARTICIPANT_STATUS_ALIVE)->where('u.health', '>', 0)
            ->groupBy('p.id')->inRandomOrder()->first();
    }

    /**
     * Gets all games
     *
     * @return Collection
     */
    public static function getGames()
    {
        return Game::get();
    }

    /**
     * Gets all games participants
     *
     * @return Collection
     */
    public static function getParticipants()
    {
        return Participant::get();
    }

    /**
     *
     * UPDATE
     *
     */

    /**
     * Reorders other participants in the game
     *
     * @param integer $gameId
     * @return void
     */
    private static function reorderOtherParticipants(int $gameId)
    {
        foreach (Participant::where('game_id', $gameId)->orderBy('order_of_play', 'DESC')->get() as $participant) {
            $participant->update([
                'order_of_play' => $participant->order_of_play + 1
            ]);
        }
    }

    /**
     * UPdates game
     *
     * @param integer $gameId
     * @param array $updates
     * @return bool
     */
    public static function updateGame(int $gameId, array $updates)
    {
        return boolval(Game::where('id', $gameId)->update($updates));
    }

    /**
     * Updates participant status not alive any more
     *
     * @param integer $gameId
     * @param integer $participantId
     * @return bool
     */
    public static function updateParticipantStatusDead(int $gameId, int $participantId)
    {
        return boolval(Participant::where([
            'game_id' => $gameId,
            'participant_id' => $participantId
        ])->update(['status' => self::PARTICIPANT_STATUS_DEAD]));
    }
}
