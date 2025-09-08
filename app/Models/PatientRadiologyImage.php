<?php

namespace App\Models;

class PatientRadiologyImage extends BaseModel
{

    protected $fillable = [
        'patient_id',
        'type',
        'image_url',
        'patient_item_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function patient_item()
    {
        return $this->belongsTo(PatientItem::class,'patient_item_id');
    }

    public function setImageUrlAttribute($value)
    {
        $this->handleFileAttribute('image_url', $value, 'patient_radiology_images','radiology_image'.'-'.$this->id);
    }
}
