<?php

namespace App\Models;

class Therapy extends BaseModel
{
    protected $fillable = [
    'cant_therapies',
     'number_installments',
    'time_in_day'];

    public function terapiable()
    {
        return $this->morphTo();
    }
}
