<?php

namespace App\Models;



class Branch extends BaseModel
{
    
    protected $fillable = ['company_id', 'name', 'address', 'phone', 'code'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class,'plan_branch')->withTimestamps();
    }

    public function items()
    {
        return $this->belongsToMany(Item::class,'item_branch')->withTimestamps();
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class,'offer_branch')->withTimestamps();
    }
    
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class,'room_branch')->withTimestamps();
    }


    public function users()
    {
        return $this->belongsToMany(User::class,'branch_user')->withTimestamps();
    }
}

//crear varios usuarios con diferentes branchs asignadas