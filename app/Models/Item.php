<?php

namespace App\Models;


class Item extends BaseModel
{
    protected $fillable = [
        'name',
        'code',
        'plan',
        'type_of_item_id',
        'commission_id',
        'price',
        'coupon_type'
    ];

    protected $hidden = ['commission_id', 'plan'];

  

    /*
    protected static function booted()
    {
        static::addGlobalScope('exclude_plans', function (Builder $builder) {
            $builder->where('plan', false);
        });
    }
    
    public function scopeOnlyPlan($query)
    {
        return $query->withoutGlobalScope('exclude_plans')->where('plan', true);
    }
    */

    public function voucher()
    {
      return $this->belongsToMany(Voucher::class,'voucher_plan_items');
    }

    //el costo del item es el su precio con comisiones, y cuando se vaya a desbloquear un item se le resta el precio con comisiones al saldo del paciente

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_item')->withTimestamps();
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'item_branch')->withTimestamps();
    }

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'voucher_patient_item', 'patient_item_id', 'voucher_id');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'item_invoice')->withTimestamps();
    }

    public function type()
    {
        return $this->belongsTo(TypeOfItem::class, 'type_of_item_id');
    }

    public function commission()
    {
        return $this->belongsTo(Commission::class);
    }

    public function waiting_room()
    {
        return $this->hasOne(WaitingRoom::class,'plan_item_id');
    }

    public function item_ars()
    {
        return $this->hasMany(ItemArs::class);
    }
}
