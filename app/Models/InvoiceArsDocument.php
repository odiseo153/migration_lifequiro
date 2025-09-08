<?php

namespace App\Models;

class InvoiceArsDocument extends BaseModel
{
    protected $fillable = [
        'invoice_id',
        'document'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function setDocumentAttribute($value)
    {
        $this->handleFileAttribute('document', $value, 'invoice_ars_documents','invoice_ars_document'.'-'.$this->id);
    }
}