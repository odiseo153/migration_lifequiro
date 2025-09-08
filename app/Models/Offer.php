<?php

namespace App\Models;

class Offer extends BaseModel
{
    protected $fillable = ['name', 'expired_date','total','commission_id'];


    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'offer_branch')->withTimestamps();
    }

  
    public function items()
    {
        return $this->belongsToMany(Item::class, 'offer_item')
        ->withPivot('price','item_id')->withTimestamps();

    }

    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

   
}


