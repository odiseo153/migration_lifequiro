<?php

namespace App\Models;


class InvoiceTransaction extends BaseModel
{
    protected $fillable = [
        'invoice_id',
        'patient_id',
        'amount',
        'transaction_type',
        'description',
    ];

    public function setAmountAttribute($value)
    {
        if ($this->attributes['transaction_type'] ?? null === 'salida') {
            $this->attributes['amount'] = -abs($value);
        } else {
            $this->attributes['amount'] = abs($value);
        }
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
