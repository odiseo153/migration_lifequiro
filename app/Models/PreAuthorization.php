<?php

namespace App\Models;

use App\Enums\AuthorizationStatus;

class PreAuthorization extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'insurance_id',
        'pre_authorization_number',
        'authorization_file',
        'item_document_count',
    ];

    protected $casts = [
        'status' => AuthorizationStatus::class,
    ];

    public function pre_items()
    {
        return $this->belongsToMany(Item::class,'pre_authorization_items','pre_authorization_id','item_id')
        ->withPivot(['quantity','authorized_porcentage','cost','total_difference','covered_by_service','difference_per_service','used','available'])
        ->withTimestamps();
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    public function pre_authorization_document()
    {
        return $this->hasOne(PreAuthorizationDocument::class);
    }

    public function programming_histories()
    {
        return $this->hasMany(ProgrammingHistory::class);
    }

    public function ars()
    {
        return $this->hasOneThrough(
            Ars::class,
            Insurance::class,
            'id', // Foreign key on the insurances table...
            'id', // Foreign key on the ars table...
            'insurance_id', // Local key on the pre_authorizations table...
            'ars_id'  // Local key on the insurances table...
        );
    }

    public function setAuthorizationFileAttribute($value)
    {
        $this->handleFileAttribute('authorization_file', $value, 'pre_authorizations','authorization_file'.'-'.$this->id);
    }
}


