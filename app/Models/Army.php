<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Army extends Model
{

    protected $table = 'armies';

    protected $fillable = [
        'name',
        'strategy',
    ];

    protected $with = ['units'];

    public function getNumOfUnitsAttribute()
    {
        return $this->units()->count();
    }

    public function units()
    {
        return $this->hasMany('App\Models\Unit', 'army_id', 'id')->where('health', '>', 0);
    }
}
