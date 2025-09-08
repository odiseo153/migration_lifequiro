<?php

namespace App\Models;


class PatientDocument extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'patient_photo',
        'security_carnet_photo',
        'document_front_photo',
        'document_back_photo',
        'patient_signature_photo'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function setPatientPhotoAttribute($value)
    {
        $this->handleFileAttribute('patient_photo', $value, 'patient_documents','patient_photo'.'-'.$this->id);
    }

    public function setSecurityCarnetPhotoAttribute($value)
    {
        $this->handleFileAttribute('security_carnet_photo', $value, 'patient_documents','security_carnet_photo'.'-'.$this->id);
    }

    public function setDocumentFrontPhotoAttribute($value)
    {
        $this->handleFileAttribute('document_front_photo', $value, 'patient_documents','document_front_photo'.'-'.$this->id);
    }

    public function setDocumentBackPhotoAttribute($value)
    {
        $this->handleFileAttribute('document_back_photo', $value, 'patient_documents','document_back_photo'.'-'.$this->id);
    }

    public function setPatientSignaturePhotoAttribute($value)
    {
        $this->handleFileAttribute('patient_signature_photo', $value, 'patient_documents','patient_signature_photo'.'-'.$this->id);
    }
}
