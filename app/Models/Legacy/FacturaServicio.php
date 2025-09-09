<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class FacturaServicio extends Model
{
    protected $connection = 'legacy';
    protected $table = 'factura_servicios';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
