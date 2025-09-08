<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $connection = 'legacy';
    protected $table = 'factura';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
