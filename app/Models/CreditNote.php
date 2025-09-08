<?php

namespace App\Models;


class CreditNote extends BaseModel
{
    protected $fillable = [
        'invoice_id',
        'patient_id',
        'amount',
        'payment_method_id',
        'note',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

}



