<?php

namespace App\Models;



class City extends BaseModel
{
    protected $fillable = ['name'];

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
}




