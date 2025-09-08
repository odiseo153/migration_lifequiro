<?php

namespace App\Models;

class Ars extends BaseModel
{
    protected $fillable = [
        'name',
    ];

    public function patients()
    {
        return $this->belongsToMany(Patient::class,'insurances')->withTimestamps();
    }

    public function item_ars()
    {
        return $this->hasMany(ItemArs::class);
    }
}

