<?php

namespace App\Models;


class TransactionType extends BaseModel
{
    protected $fillable = [
        'name',
    ];

    public function invoice()
    {
        return $this->hasMany(Invoice::class);
    }

}
