<?php

namespace App\Models;


class Receipt extends BaseModel
{
    protected $fillable = [
        'amount',
        'payment_method_id',
        'user_id',
        'invoice_id',
    ];

    protected $appends = ['payment_method_name','user_name','no_invoice','patient','status'];

    public function getNoInvoiceAttribute()
    {
        $invoice = $this->invoice;
        if($invoice){
            return $invoice->no_invoice;
        }
        return null;
    }


    public function getStatusAttribute()
    {
        return $this->deleted_at ? 'Anulado' : 'Activo';
    }

    public function getPatientAttribute()
    {
        $invoice = $this->invoice;
        if($invoice){
            return [
                'id' => $invoice->patient->id,
                'first_name' => $invoice->patient->first_name,
                'last_name' => $invoice->patient->last_name,
                'full_name' => $invoice->patient->first_name . ' ' . $invoice->patient->last_name,
                'birth_date' => $invoice->patient->birth_date,
                'email' => $invoice->patient->email,
                'mobile' => $invoice->patient->mobile,
            ];
        }
        return null;
    }

 

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class)->select('id','first_name','last_name');
    }

    public function patient()
    {
        return $this->hasOneThrough(Patient::class,Invoice::class,'id','id','invoice_id','patient_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function services()
    {
        return $this->hasMany(PatientItem::class,'invoice_id','invoice_id')
        ->select('id','invoice_id','quantity','item_id','discount','total','type','individual_cost','no_document')
        ->with('item:id,name,type_of_item_id');
    }

    public function getPaymentMethodNameAttribute()
    {
        return $this->paymentMethod->name;
    }

    public function getUserNameAttribute()
    {
        return $this->user->first_name . ' ' . $this->user->last_name;
    }
}
