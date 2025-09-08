<?php

namespace App\Models;


class Bill extends BaseModel
{
    protected $fillable = [
        'description',
        'amount',
        'branch_id',
        'user_id',
        'delivery_to',
    ];
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
