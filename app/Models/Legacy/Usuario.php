<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $connection = 'legacy';
    protected $table = 'usuario';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
