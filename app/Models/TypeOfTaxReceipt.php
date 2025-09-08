<?php

namespace App\Models;


class TypeOfTaxReceipt extends BaseModel
{
    protected $fillable = [
        'name'
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
