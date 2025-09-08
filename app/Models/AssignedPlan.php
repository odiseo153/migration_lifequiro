<?php

namespace App\Models;

use App\Enums\PlanStatus;
use App\Enums\ItemType;

class AssignedPlan extends BaseModel
{
    protected $fillable = [
        'plan_id',
        'patient_id',
        'date_start',
        'date_end',
        'plan_name',
        'paid_type',
        'amount',
        'therapies_number',
        'number_installments',
        'status',
        'branch_id',
        'user_id',
        'card_commission',
        'bank_commission',
        'other_commission'
    ];




    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->hasMany(AcquiredService::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function voucher()
    {
        return $this->hasMany(Voucher::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function descuentAuthorizations()
    {
        return $this->hasMany(DescuentAuthorization::class);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class)->orderBy('date_paid');
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function transactions()
    {
        return $this->hasMany(PlanTransaction::class);
    }

    public function isCompleted()
    {
        $total_sessions = $this->plan->total_sessions ?? 0;

        $used_sessions = $this->patient->acquired_services()
        ->whereNotNull('plan_item_id')
        ->whereNotNull('assigned_plan_id')
        ->whereHas('patient_plan_item', function($query){
            $query->where('type_of_item_id', ItemType::AJUSTE->value);
        })
        ->where('assigned_plan_id', $this->id)
        ->count();

        $remaining_sessions = max(0, $total_sessions - $used_sessions);

        // Calculate therapy metrics
        $therapies_number = $this->therapies_number ?? 0;

        $used_therapies = $this->patient->acquired_services()
        ->whereNotNull('plan_item_id')
        ->whereNotNull('assigned_plan_id')
        ->whereHas('patient_plan_item', function($query){
            $query->where('type_of_item_id', ItemType::TERAPIA_FISICA->value);
        })
        ->where('assigned_plan_id', $this->id)
        ->count();

        $remaining_therapies = max(0, $therapies_number - $used_therapies);

        $is_completed = $remaining_sessions == 0 && $remaining_therapies == 0;

        if($is_completed && $this->status != PlanStatus::Completado){
            $this->status = PlanStatus::Completado;
        }

        return $is_completed;
    }

    public function ScheduledAppointments()
    {
        return $this->hasMany(ProgrammingHistory::class);
    }
}







