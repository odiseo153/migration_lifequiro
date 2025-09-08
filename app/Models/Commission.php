<?php

namespace App\Models;



class Commission extends BaseModel
{
    protected $fillable = [
        'card_commission',
         'bank_commission',
          'other_commission' 
        ];

    public function plans()
    {
        return $this->hasOne(Plan::class);
    }

    public function offers()
    {
        return $this->hasOne(Offer::class);
    }

    public function items()
    {
        return $this->hasOne(Item::class);
    }

}
