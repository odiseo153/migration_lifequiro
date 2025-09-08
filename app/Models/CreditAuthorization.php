<?php

namespace App\Models;

use App\Enums\AuthorizationType;
use App\Enums\AuthorizationStatus;

class CreditAuthorization extends BaseModel
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
        'expiration_date',
    ];

    protected $casts = [
        'type' => AuthorizationType::class,
        'status' => AuthorizationStatus::class,
    ];

    public function patient(){
        return $this->belongsTo(Patient::class);
    }

    public function authorizedBy(){
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function requestBy(){
        return $this->belongsTo(User::class, 'request_by');
    }

    /**
     * Relationship with AuthorizationConsume records
     */
    public function consumptions()
    {
        return $this->hasMany(AuthorizationConsume::class, 'authorization_id')
            ->where('authorization_type', 'credit');
    }

    /**
     * Get total consumed amount for this authorization
     */
    public function getTotalConsumedAttribute(): float
    {
        return $this->consumptions->sum('consumed_amount');
    }

    /**
     * Get remaining amount that can still be consumed
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->approved_amount - $this->getTotalConsumedAttribute();
    }

    /**
     * Check if authorization has remaining amount to consume
     */
    public function hasRemainingAmount(): bool
    {
        return $this->getRemainingAmountAttribute() > 0;
    }

    /**
     * Check if a specific amount can be consumed
     */
    public function canConsume(float $amount): bool
    {
        return $this->getRemainingAmountAttribute() >= $amount;
    }
}
