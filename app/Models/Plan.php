<?php

namespace App\Models;


class Plan extends BaseModel
{
    protected $fillable = [
        'id',
        'name',
        'code',
        'price',
        'total_sessions',
        'type_of_plan_id',
        'therapies_number',
        'number_installments',
        'duration',
        'commission_id',
        'available'
    ];

    protected $appends = ['type_of_plan_name'];

    public function getTypeOfPlanNameAttribute(): string
    {
        return $this->type_of_plan->name;
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'plan_branch')
        ->orderBy('branches.id', 'desc')
                    ->withTimestamps();
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class,'item_branch');
    }

    public function assigned_plan()
    {
        return $this->hasMany(AssignedPlan::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class,'plan_invoice')->withTimestamps();
    }

    public function type_of_plan()
    {
        return $this->belongsTo(TypeOfPlan::class,'type_of_plan_id');
    }
}
