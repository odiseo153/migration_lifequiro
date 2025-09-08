<?php

namespace App\Models;

class ServiceDocument extends BaseModel
{
    protected $fillable = [
        'service_id',
        'document'
    ];

    public function service()
    {
        return $this->belongsTo(PatientItem::class, 'service_id');
    }

    public function setDocumentAttribute($value)
    {
        $this->handleFileAttribute('document', $value, 'service_documents','service_document'.'-'.$this->id);
    }
}