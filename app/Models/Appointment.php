<?php

namespace App\Models;

use App\Enums\PlanStatus;
use App\Enums\AppointmentType;
use App\Enums\AppointmentStatus;
use App\Events\AppointmentModificated;


class Appointment extends BaseModel
{
    protected $fillable = [
        'note',
        'date',
        'hour',
        'type_of_appointment_id',
        'branch_id',
        'status_id',
        'patient_id',
        'insurance_id',
        'is_calling',
        'no_asistence_comment',
        'assigned_plan_id'
    ];

    protected $appends = ['status_name','is_cancel', 'type_name', 'formatted_hour', 'patient_group','alerts','no_response']; // Agregar un atributo accesible

    protected $hidden = ['type_of_appointment_id', 'updated_at'];


    public function comments()
    {
        return $this->hasMany(AppointmentBinacle::class);
    }

    public function assigned_plan()
    {
        return $this->belongsTo(AssignedPlan::class);
    }


    public function cancelled()
    {
        return $this->hasOne(CancelledAppointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function call_histories()
    {
        return $this->hasMany(CallHistory::class);
    }

    public function no_responses()
    {
        return $this->hasMany(NoResponse::class);
    }

    public function voucher()
    {
        return $this->hasOne(Voucher::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function services()
    {
        return $this->hasManyThrough(PatientItem::class, Voucher::class, 'appointment_id', 'id', 'id', 'id')
            ->join('voucher_patient_item', 'patient_items.id', '=', 'voucher_patient_item.patient_item_id')
            ->whereColumn('vouchers.id', 'voucher_patient_item.voucher_id');
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    public function typeOfAppoinment()
    {
        return $this->belongsTo(TypeOfAppointments::class, 'type_of_appointment_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function getStatusNameAttribute()
    {
        return $this->status() ? $this->status()->value('state') : null;
    }

    public function getTypeNameAttribute()
    {
        $patient = $this->patient;
        //$used_sessions = $patient ? $patient->acquired_services()->count() : 0;
        //$total_sessions = $patient && $patient->assigned_plan ? ($patient->assigned_plan->plan->total_sessions ?? 0 ) : 0;
        //$nextSession = $used_sessions + 1;

        /*
        // Si son 24 sesiones y va por la 21, o si son 36 y va por la 33, la cita debe ser tipo Radiografía RC
        if (
            ($total_sessions == 24 && $nextSession == 21) ||
            ($total_sessions == 36 && $nextSession == 33)
        ) {
            return 'Radiografía RC';
        }
        */

        $types_not_mr = [
            AppointmentType::CONSULTA->value,
            AppointmentType::RADIOGRAFIA->value,
            AppointmentType::REPORTE->value,
            AppointmentType::COMPARACION->value,
            AppointmentType::AP->value,
            AppointmentType::MIP->value,
            AppointmentType::RADIOGRAFIA_RC->value,
        ];

        if(in_array($this->typeOfAppoinment->id, $types_not_mr) || $this->status_id === AppointmentStatus::COMPLETADA->value){
            return $this->typeOfAppoinment->name;
        }

        return $patient && ($patient->assigned_plan || $patient->preAuthorization)
        ? ($patient->assigned_plan?->plan?->type_of_plan?->name == 'VIP' ? 'MR VIP' : 'MR')
        : ($this->typeOfAppoinment ? $this->typeOfAppoinment->name : null);

        //return $this->typeOfAppoinment() ? $this->typeOfAppoinment()->value('name') : null;
    }

    public function getPatientGroupAttribute()
    {
        return $this->patient && $this->patient->insurance && $this->patient->insurance->Ars ? $this->patient->insurance->Ars->name : null;
    }

    public function getFormattedHourAttribute()
    {
        // Convertir la hora de formato 24 horas a 12 horas con AM/PM
        return $this->hour ? date('h:i A', strtotime($this->hour)) : null;
    }

    public function getNoResponseAttribute()
    {
        return $this->no_responses ? "No Respondio" : null;
    }

    public function getIsCancelAttribute()
    {
        return $this->cancelled ? true : false;
    }

    public function getAlertsAttribute()
    {
        $alerts = [];

        $patient = $this->patient;
        if($patient->assigned_plan){
            if($patient->assigned_plan->date_end < now()){
                $alerts[] = 'plan vencido';
            }

            if($patient->assigned_plan->status == PlanStatus::Expirado->value){
                $alerts[] = 'plan expirado';
            }

            if($patient->assigned_plan->isCompleted()){
                $alerts[] = 'plan completado';
            }
        }

        if($patient->is_birthday){
                $alerts[] = 'cumpleaños';
        }

        return $alerts;
    }

/*
protected static function booted()
{
        static::updated(function ($appointment) {
            $appointment->refresh();
            event(new AppointmentModificated($appointment, 'update'));
        });

        static::created(function ($appointment) {
            event(new AppointmentModificated($appointment, 'create'));
        });
    }
    */


}
