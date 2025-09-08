<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $connection = 'legacy';
    protected $table = 'paciente';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
