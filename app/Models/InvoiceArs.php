<?php

namespace App\Models;


class InvoiceArs extends BaseModel
{
    protected $fillable = [
        'ncf',
        'template',
        'ars_id',
        'total',
        'pending',
        'code',
    ];

    protected $appends = ['paid_amount'];

    public function services()
    {
        return $this->belongsToMany(Invoice::class,'services_invoice_ars','invoice_ars_id','invoice_id');
    }

    public function patient()
    {
        return $this->hasManyThrough(Patient::class,Invoice::class,'id','id','invoice_id','patient_id');
    }

    public function ars()
    {
        return $this->belongsTo(Ars::class,'ars_id');
    }

    public function getPaidAmountAttribute()
    {
        return (int)$this->services()->sum('ars_paid_amount');
    }

    public function getPendingAttribute()
    {
        return $this->attributes['pending'] - $this->services()->sum('ars_paid_amount');
    }

}
