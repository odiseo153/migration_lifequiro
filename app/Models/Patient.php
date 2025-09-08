<?php

namespace App\Models;

use Carbon\Carbon;
use App\Enums\ServicesStatus;
use App\Enums\AppointmentType;
use App\Models\PlanTransaction;

class Patient extends AuthenticablePatient
{
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'token',
        'email',
        'birth_date',
        'phone',
        'mobile',
        'identity_document_type',
        'identity_document',
        'gender',
        'occupation',
        'civil_status',
        'address',
        'comment',
        'province_id',
        'city_id',
        'sector_id',
        'branch_id',
        'where_met_us_id',
        'patient_group_id'
    ];

    protected $appends = ['is_birthday','age','patient_code','type_patient','where_he_met_us_name','last_appointment'];
   // protected $with = ['assigned_plan'];

    public function waiting_rooms()
    {
        return $this->hasMany(WaitingRoom::class);
    }

    public function medical_terapia_tracion_module()
    {
        return $this->hasMany(MedicalTerapiaTracionModule::class);
    }

    public function patient_items()
    {
        return $this->hasMany(PatientItem::class);
    }

    public function medical_radiography_module()
    {
        return $this->hasMany(PatientRadiologyImage::class);
    }

    public function physical_therapy_categories()
    {
        return $this->belongsToMany(PhysicalTherapyCategory::class, 'physical_therapy_categories_patient', 'patient_id', 'category_id');
    }

    public function medical_ajuste_module()
    {
        return $this->hasMany(MedicalAjusteModule::class);
    }

    public function medical_consultation_module()
    {
        return $this->hasMany(MedicalConsultationModule::class);
    }

    public function medical_comparacion_report_module()
    {
        return $this->hasMany(MedicalComparacionReporteModule::class);
    }

    public function patient_radiology_images()
    {
        return $this->hasMany(PatientRadiologyImage::class);
    }

    public function card_items()
    {
        return $this->belongsToMany(Item::class, 'patient_items', 'patient_id', 'item_id')
                    ->withPivot( 'invoice_id','paid__with_card','individual_cost','quantity', 'discount', 'total','type','id','deleted_at','no_document')
                    ->orderBy('patient_items.id','desc')
                    //->where('patient_items.quantity','>','0')
                    ->whereNull('patient_items.deleted_at')
                    ->withTimestamps();
    }

    public function acquired_services()
    {
        return $this->hasMany(AcquiredService::class);
    }

    public function call_history()
    {
        return $this->hasMany(Call::class);
    }

    public function ars()
    {
        return $this->belongsToMany(Ars::class,'insurances');
    }

    public function itemsUnlocked()
    {
        return $this->belongsToMany(PatientItem::class, 'acquired_services')
            ->whereNull('acquired_services.deleted_at')
            ->withTimestamps();
    }

    public function transactions()
    {
        return $this->belongsToMany(PlanTransaction::class)->withTimestamps();
    }

    public function insurance()
    {
        return $this->hasOne(Insurance::class);
    }

    public function medical_record()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function neurological_and_functional_evaluation()
    {
        return $this->hasOne(NeurologicalAndFunctionalEvaluation::class);
    }

    public function coupon()
    {
        return $this->hasMany(Coupon::class);
    }

    public function creditNote()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function felicitation_card()
    {
        return $this->hasMany(CouponNotification::class);
    }

    public function preAuthorization()
    {
        return $this->hasOne(PreAuthorization::class);
    }

    public function preAuthorizations()
    {
        return $this->hasMany(PreAuthorization::class)->withTrashed();
    }

    public function patient_document()
    {
        return $this->hasOne(PatientDocument::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class,'branch_id');
    }

    public function plan_transactions()
    {
        return $this->hasMany(PlanTransaction::class);
    }

    public function where_he_met_us()
    {
        return $this->belongsTo(WhereHeMetUs::class,'where_met_us_id');
    }

    public function medical_data()
    {
        return $this->hasOne( PatientMedicalData::class);
    }

    public function assigned_plan()
    {
        return $this->hasOne( AssignedPlan::class);
    }

    public function physical_examination()
    {
        return $this->hasOne( PhysicalExamination::class);
    }

    public function spine_evaluation()
    {
        return $this->hasOne( SpineEvaluation::class);
    }

    public function diagnosis_and_treatment()
    {
        return $this->hasOne( DiagnosisAndTreatment::class);
    }

    public function patient_progress_after_treatment()
    {
        return $this->hasOne( PatientProgressAfterTreatment::class);
    }


    public function history_medical()
    {
        return $this->hasOne( HistoryMedical::class);
    }

    public function patient_group()
    {
        return $this->belongsTo(PatientGroup::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function emergency_contacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function insurances()
    {
        return $this->hasMany(Insurance::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function MedicalTerapiaTracionModule()
    {
        return $this->hasMany(MedicalTerapiaTracionModule::class);
    }

    public function MedicalAjusteModule()
    {
        return $this->hasMany(MedicalAjusteModule::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function related_in_center()
    {
        return $this->hasMany(RelatedInCenter::class);
    }

    public function programming_history_notes()
    {
        return $this->hasMany(ProgrammingHistoryNotes::class);
    }

    public function programming_history()
    {
        return $this->hasMany(ProgrammingHistory::class);
    }
    public function binnacles()
    {
        return $this->hasMany(Binnacle::class);
    }

    public function getIsBirthdayAttribute(): bool
    {
        if (!$this->birth_date) {
            return false;
        }

        $birthDate = Carbon::parse($this->birth_date);
        $today = Carbon::today();

        return $birthDate->month === $today->month &&
               $birthDate->day === $today->day;
    }

    public function getAgeAttribute(): int
    {
        if (!$this->birth_date) {
            return 0;
        }

        $birthDate = Carbon::parse($this->birth_date);
        $today = Carbon::today();

        $years = $birthDate->diffInYears($today);

        return (int)$years;
    }

    public function getLastAppointmentAttribute()
    {
        $last_appointment = $this->appointments()->orderBy('created_at', 'desc')->first();

        if(!$last_appointment){
            return [];
        }

        return [
            'id' => $last_appointment->id,
            'date' => $last_appointment->date,
            'hour' => $last_appointment->hour,
            'type_of_appointment' => $last_appointment->type_of_appointment,
            'status' => $last_appointment->status_id,
        ];
    }

    public function getPatientCodeAttribute(): string
    {
        return $this->branch ? $this->branch->code . $this->id : $this->id;
    }

    public function getHasAFullCycle()
    {
        return $this->appointments()
        ->whereIn('type_of_appointment_id', [
            AppointmentType::CONSULTA->value,
            AppointmentType::RADIOGRAFIA->value,
            AppointmentType::REPORTE->value,
        ])
        ->count() >= 3;
    }

    public function getTypePatient()
    {

        if ($this->assigned_plan) {
            return ($this->assigned_plan->plan->type_of_plan->name == 'VIP' ? 'MR VIP' : 'MR');
        }

        if ($this->getHasAFullCycle()) {
            return 'MIP';
        }
        return null;
    }

    public function scopePatientType($query, $type)
    {
        return $query->where(function ($q) use ($type) {
            // Caso MR VIP o MR
            if ($type === 'MR VIP' || $type === 'MR') {
                $q->whereHas('assigned_plan.plan.type_of_plan', function ($sub) use ($type) {
                    if ($type === 'MR VIP') {
                        $sub->where('name', 'VIP');
                    } else { // MR
                        $sub->where('name', '!=', 'VIP');
                    }
                });
            }

            // Caso MIP (full cycle)
            if ($type === 'MIP') {
                $q->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('appointments')
                        ->whereColumn('appointments.patient_id', 'patients.id')
                        ->whereIn('type_of_appointment_id', [
                            AppointmentType::CONSULTA->value,
                            AppointmentType::RADIOGRAFIA->value,
                            AppointmentType::REPORTE->value,
                        ])
                        ->whereNull('appointments.deleted_at')
                        ->havingRaw('COUNT(*) >= 3');
                })->whereDoesntHave('assigned_plan');
            }
        });
    }

    public function NextPlanItem()
    {
        // Obtener todos los servicios consumidos ordenados por fecha (solo items de plan)
        $acquiredServiceItems = $this->acquired_services()
            ->whereNotNull('plan_item_id')
            ->whereNotNull('assigned_plan_id')
            ->where('status', ServicesStatus::COMPLETADA->value)
            ->with(['item', 'patient_plan_item'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->filter(fn($service) => $service->plan_item_id && in_array(optional($service->patient_plan_item)->type_of_item_id, [5, 7])); // Solo ajustes y terapia física de plan

        if($this->assigned_plan){
            $acquiredServiceItems = $acquiredServiceItems->where('assigned_plan_id', $this->assigned_plan->id);
        }

        // Crear un array con el historial de servicios consumidos (solo items de plan)
        $serviceHistory = [];

        foreach ($acquiredServiceItems as $service) {
            if (optional($service->patient_plan_item)->type_of_item_id) {
                $serviceHistory[] = [
                    'type_id' => $service->patient_plan_item->type_of_item_id,
                    'type_name' => $service->patient_plan_item->type_of_item_id === 7 ? 'Ajuste' : 'Terapia Física',
                    'created_at' => $service->created_at,
                    'service' => $service,
                    'plan_item' => $service->patient_plan_item
                ];
            }
        }

        // Determinar el siguiente item basado en el patrón: 2 ajustes, 1 terapia, 2 ajustes, 1 terapia...
        $totalServices = count($serviceHistory);
        $expectedTypeId = 7; // Por defecto Ajuste
        $expectedTypeName = 'Ajuste';

        if ($totalServices === 0) {
            // Si no hay servicios, comenzar con ajuste
            $expectedTypeId = 7;
            $expectedTypeName = 'Ajuste';
        } else {
            // Determinar la posición en el ciclo (2 ajustes + 1 terapia = ciclo de 3)
            $positionInCycle = $totalServices % 3;

            switch ($positionInCycle) {
                case 0: // Posición 0: primer ajuste del ciclo
                case 1: // Posición 1: segundo ajuste del ciclo
                    $expectedTypeId = 7;
                    $expectedTypeName = 'Ajuste';
                    break;
                case 2: // Posición 2: terapia física
                    $expectedTypeId = 5;
                    $expectedTypeName = 'Terapia Física';
                    break;
            }
        }

        return [
            'name' => $expectedTypeName,           // Nombre del próximo item
            'type_of_item_id' => $expectedTypeId,  // ID del tipo de item
            'next_sequence' => $this->getNextSequenceInfo($totalServices) // Info de secuencia
        ];
    }

    private function getNextSequenceInfo($totalServices)
    {
        $positionInCycle = $totalServices % 3;
        $cycleNumber = intval($totalServices / 3) + 1;

        switch ($positionInCycle) {
            case 0:
                return "Ciclo {$cycleNumber} - Primer Ajuste";
            case 1:
                return "Ciclo {$cycleNumber} - Segundo Ajuste";
            case 2:
                return "Ciclo {$cycleNumber} - Terapia Física";
        }
    }

    public function getTypePatientAttribute()
    {
        return $this->getTypePatient();
    }

    public function getWhereHeMetUsNameAttribute()
    {
        return $this->where_he_met_us() ? $this->where_he_met_us()->value('name') : null;
    }
}






