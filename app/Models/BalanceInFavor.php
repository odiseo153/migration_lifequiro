<?php

namespace App\Models;


class BalanceInFavor extends BaseModel
{
    protected $fillable = [
        'invoice_id',
        'patient_id',
        'amount',
        'payment_method_id',
        'description',
    ];

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
