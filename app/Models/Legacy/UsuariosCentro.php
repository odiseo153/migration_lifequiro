<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class UsuariosCentro extends Model
{
    protected $connection = 'legacy';
    protected $table = 'usuarios_centro';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
