<?php

namespace App\Models;


class Schedule extends BaseModel
{

    protected $fillable = [
        'branch_id',
        'day',
        'hour',
        'available'
    ];

    public function branches()
    {
        return $this->belongsTo(Branch::class);
    }
}