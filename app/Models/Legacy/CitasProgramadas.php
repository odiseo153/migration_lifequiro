<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class CitasProgramadas extends Model
{
    protected $connection = 'legacy';
    protected $table = 'citas_automaticas';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
