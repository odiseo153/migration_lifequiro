<?php

namespace App\Models;

use App\Enums\AuthorizationType;
use App\Enums\AuthorizationStatus;


class Authorization extends BaseModel
{
    protected $fillable = [
        'type',
        'request_amount',
        'approved_amount',
        'patient_id',
        'comment',
        'status',
        'request_by',
        'authorized_by',
        'authorized_at',
        'item_id',
    ];

    protected $casts = [
        'type' => AuthorizationType::class,
        'status' => AuthorizationStatus::class,
    ];

    public function patient(){
        return $this->belongsTo(Patient::class);
    }

    public function item(){
        return $this->belongsTo(Item::class);
    }

    public function requestBy(){
        return $this->belongsTo(User::class, 'request_by');
    }

    public function authorizedBy(){
        return $this->belongsTo(User::class, 'authorized_by');
    }

    

}
