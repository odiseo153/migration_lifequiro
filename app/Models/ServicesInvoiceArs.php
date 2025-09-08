<?php

namespace App\Models;


class ServicesInvoiceArs extends BaseModel
{

    protected $fillable = ['invoice_ars_id', 'invoice_id','paid_amount'];

    public function invoiceArs()
    {
        return $this->belongsTo(InvoiceArs::class,'invoice_ars_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class,'invoice_id');
    }
}
