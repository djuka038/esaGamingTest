<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'games';

    protected $fillable = [
        'status'
    ];

    protected $visible = [
        'id',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $with = [
        'participants',
    ];

    public function participants()
    {
        return $this->belongsToMany('App\Models\Army', 'game_participants', 'game_id', 'participant_id')
            ->orderBy('order_of_play', 'ASC');
    }
}
