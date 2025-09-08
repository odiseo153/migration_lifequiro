<?php

namespace App\Models;


class PhysicalTherapyCategory extends BaseModel
{
    protected $fillable = ['name', 'description', 'father_id','type'];

    public function father()
    {
        return $this->belongsTo(PhysicalTherapyCategory::class, 'father_id');
    }

    public function children()
    {
        return $this->hasMany(PhysicalTherapyCategory::class, 'father_id');
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'physical_therapy_categories_patient', 'category_id', 'patient_id');
    }
}
