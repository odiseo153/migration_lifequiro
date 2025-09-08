<?php

namespace App\Models;


class WaitingRoomStatus extends BaseModel
{
    protected $fillable = [
        'status',
    ];

    public function waiting_rooms()
    {
        return $this->hasMany(WaitingRoom::class);
    }
    
}
