<?php

namespace App\Models;


class Insurance extends BaseModel
{
    protected $table = 'insurances';
    

    protected $fillable = [
        'image_insurance',
        'is_active',
        'no_afiliado',
        'patient_id',
        'ars_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function Ars()
    {
        return $this->belongsTo(Ars::class);
    }

    public function setImageInsuranceAttribute($value)
    {
        $this->handleFileAttribute('image_insurance', $value, 'insurances/'.$this->no_afiliado.'-'.$this->id);
    }
}
