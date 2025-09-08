<?php

namespace App\Models;

class Province extends BaseModel
{
    protected $fillable = ['name'];

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
}
