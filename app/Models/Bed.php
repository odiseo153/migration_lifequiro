<?php

namespace App\Models;


class Bed extends BaseModel

{
    protected $fillable = ['name','room_id'];

    public function room()
    {
        return $this->belongsTo(Room::class,'room_id');
    }
    
    public function waiting_room()
    {
        return $this->hasMany(WaitingRoom::class);
    }
}



