<?php

namespace App\Models;


class PaymentMethod extends BaseModel
{
    protected $fillable = [
        'name'
    ];

public function invoices()
{
    return $this->hasMany(Invoice::class);
}
}
