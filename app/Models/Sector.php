<?php

namespace App\Models;

class Sector extends BaseModel
{
    protected $fillable = ['name'];

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
}
