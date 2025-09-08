<?php

namespace App\Models;

class SpineEvaluation extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'cervical',
        'dorsal',
        'lumbar',
        'sacral',

        'flexion_degrees',
        'flexion_fm',

        'extension_degrees',
        'extension_fm',

        'right_lateral_flexion_degrees',
        'right_lateral_flexion_fm',

        'left_lateral_flexion_degrees',
        'left_lateral_flexion_fm',

        'right_rotation_degrees',
        'right_rotation_fm',

        'left_rotation_degrees',
        'left_rotation_fm',
    ];

    public function patient(){
        return $this->belongsTo(Patient::class);
    }
}
