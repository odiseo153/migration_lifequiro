<?php

namespace App\Models;


class PaymentMethod extends BaseModel
{
    protected $fillable = [
        'id',
        'name',
        'created_at',
    ];

public function invoices()
{
    return $this->hasMany(Invoice::class);
}
}
