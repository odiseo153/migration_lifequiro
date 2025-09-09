<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class FormaPago extends Model
{
    protected $connection = 'legacy';
    protected $table = 'forma_pago';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
