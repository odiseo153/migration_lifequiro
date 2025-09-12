<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $connection = 'legacy';
    protected $table = 'balance_sin_plan';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
