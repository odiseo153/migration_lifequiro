<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class AntecedenteZonasDolor extends Model
{
    protected $connection = 'legacy';
    protected $table = 'antecedentes_pain_zone';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
