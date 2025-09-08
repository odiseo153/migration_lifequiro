<?php

namespace App\Models;


class ItemArs extends BaseModel
{
    protected $fillable = [
        'item_id',
        'ars_id',
        'coberture',
        'co_payment',
    ];

    public function item(){
        return $this->belongsTo(Item::class);
    }

    public function ars(){
        return $this->belongsTo(Ars::class);
    }
}
