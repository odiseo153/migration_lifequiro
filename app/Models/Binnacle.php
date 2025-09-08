<?php

namespace App\Models;


class Binnacle extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'comment',
        'user_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
