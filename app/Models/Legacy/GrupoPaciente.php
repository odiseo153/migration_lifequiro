<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class GrupoPaciente extends Model
{
    protected $connection = 'legacy';
    protected $table = 'grupo_paciente';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
