<?php

namespace App\Models;

class Coupon extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'branch_id',
        'user_id',
        'used_by',
        'type',
        'code',
        'expiration_date',
        'is_used',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function consumer()
    {
        return $this->belongsTo(Patient::class, 'used_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
