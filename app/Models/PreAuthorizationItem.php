<?php

namespace App\Models;

class PreAuthorizationItem extends BaseModel
{
    protected $fillable = [
        'pre_authorization_id',
        'item_id',
        'no_document',
        'is_paid',
        'quantity',
        'used',
        'available',
        'authorized_porcentage',
        'cost',
        'total_difference',
        'covered_by_service',
        'difference_per_service',
    ];

    public function preAuthorization()
    {
        return $this->belongsTo(PreAuthorization::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function preItem()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
