<?php

namespace App\Models;


class InvoiceItem extends BaseModel
{
    protected $fillable = [
        'item_id',
        'invoice_id',
        'description',
        'quantity',
        'individual_cost',
        'discount',
        'total'
    ];



    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}


