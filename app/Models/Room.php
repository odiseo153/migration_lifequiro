<?php

namespace App\Models;

class Room extends BaseModel
{
    protected $fillable = ['name'];
    protected $appends = ['beds_count'];

    public function beds()
    {
        return $this->hasMany(Bed::class); 
    }

    public function waiting_room()
    {
        return $this->hasMany(WaitingRoom::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class,'room_branch')
        ->orderBy('room_branch.id','asc')
        ->withTimestamps();
    }

    public function getBedsCountAttribute()
    {
        return $this->beds()->count();
    }
}

