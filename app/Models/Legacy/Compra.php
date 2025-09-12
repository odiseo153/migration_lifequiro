<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $connection = 'legacy';
    protected $table = 'compras';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
