<?php

namespace App\Models;

use App\Enums\AuthorizationConsumptionType;

class AuthorizationConsume extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'invoice_id',
        'authorization_type',
        'authorization_id',
        'authorized_amount',
        'consumed_amount',
        'remaining_amount',
        'item_id',
        'authorized_by',
        'consumed_at',
        'notes',
    ];

    protected $casts = [
        'authorization_type' => AuthorizationConsumptionType::class,
        'authorized_amount' => 'decimal:2',
        'consumed_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'consumed_at' => 'datetime',
    ];

    /**
     * Relationship with Patient
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relationship with Invoice
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Relationship with Item
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Relationship with User who authorized
     */
    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Polymorphic relationship to authorization (DescuentAuthorization or CreditAuthorization)
     */
    public function authorization()
    {
        return $this->morphTo('authorization', 'authorization_type', 'authorization_id');
    }

    /**
     * Relationship with DescuentAuthorization
     */
    public function discountAuthorization()
    {
        return $this->belongsTo(DescuentAuthorization::class, 'authorization_id')
            ->where('authorization_type', AuthorizationConsumptionType::DISCOUNT);
    }

    /**
     * Relationship with CreditAuthorization
     */
    public function creditAuthorization()
    {
        return $this->belongsTo(CreditAuthorization::class, 'authorization_id')
            ->where('authorization_type', AuthorizationConsumptionType::CREDIT);
    }

    /**
     * Scope to filter by authorization type
     */
    public function scopeByAuthorizationType($query, AuthorizationConsumptionType $type)
    {
        return $query->where('authorization_type', $type);
    }

    /**
     * Scope to filter by patient
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Check if there's remaining amount to consume
     */
    public function hasRemainingAmount(): bool
    {
        return $this->remaining_amount > 0;
    }

    /**
     * Calculate remaining amount after potential consumption
     */
    public function calculateRemainingAfterConsumption(float $amount): float
    {
        return $this->remaining_amount - $amount;
    }

    /**
     * Check if amount can be consumed without exceeding authorization
     */
    public function canConsume(float $amount): bool
    {
        return $this->calculateRemainingAfterConsumption($amount) >= 0;
    }
}
