<?php

namespace App\Models;


class WaitingRoom extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'user_id',
        'room_id',
        'patient_item_id',
        'plan_item_id',
        'bed_id',
        //'service_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    /*
    public function acquired_service()
    {
        return $this->belongsTo(User::class,'service_id');
    }
    */

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function patient_item()
    {
        return $this->belongsTo(PatientItem::class);
    }

    public function patient_plan_item()
    {
        return $this->belongsTo(Item::class,'plan_item_id');
    }
}
