<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class HistorialAjuste extends Model
{
    protected $connection = 'legacy';
    protected $table = 'historial_ajuste';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
