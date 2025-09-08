<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PlanCuotas extends Model
{
    protected $connection = 'legacy';
    protected $table = 'plan_cuotas';
    public $timestamps = false; // la tabla vieja no tiene created_at ni updated_at
}
