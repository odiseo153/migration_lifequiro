<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class HistorialTerapia extends Model
{
    protected $connection = 'legacy';
    protected $table = 'historial_terapia';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
