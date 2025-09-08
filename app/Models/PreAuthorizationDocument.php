<?php

namespace App\Models;

class PreAuthorizationDocument extends BaseModel
{
    protected $fillable = [
        'pre_authorization_id',
        'document',
    ];

    public function pre_authorization()
    {
        return $this->belongsTo(PreAuthorization::class);
    }

    public function setDocumentAttribute($value)
    {
        $this->handleFileAttribute('document', $value, 'pre_authorizations','pre_authorization_document'.'-'.$this->id);
    }
}
