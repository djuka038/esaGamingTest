<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{

    protected $table = 'game_participants';

    protected $fillable = [
        'game_id',
        'participant_id',
        'order_of_play',
        'status'
    ];

    public function army()
    {
        return $this->hasOne('App\Models\Army', 'id', 'participant_id');
    }
}
