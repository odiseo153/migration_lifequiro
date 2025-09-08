<?php

namespace App\Models;


class Invoice extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'branch_id',
        'payment_method_id',
        'transaction_type_id',
        'no_invoice',
        'invoice_token',
        'total',
        'type_of_tax_receipt_id',
        'note',
        'pre_authorization_id',
        'no_document',

        //Ars fields
        'status_ars',
        'no_authorization',
        'ars_state',
        'ars_paid_amount',
        'is_approved',
        'is_completed',
        'ars_id',
    ];

    

    protected $appends = ['status','subtotal','discount'];

    public function getStatusAttribute()
    {
        $credit = $this->creditInvoice;

        if($credit && $credit->amount > 0){
            return 'Pendiente';
        }
        return 'Pagada';
    }

    public function getSubtotalAttribute()
    {
        return $this->services()->sum('total');
    }


    public function getDiscountAttribute()
    {
        return $this->services()->sum('discount');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function ars()
    {
        return $this->belongsTo(Ars::class);
    }

    public function invoice_ars()
    {
        return $this->hasOne(ServicesInvoiceArs::class);
    }

    public function ArsDocument()
    {
        return $this->hasOne(InvoiceArsDocument::class);
    }

    public function transaction_type()
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function type_of_tax_receipt()
    {
        return $this->belongsTo(TypeOfTaxReceipt::class);
    }

    public function preAuthorization()
    {
        return $this->belongsTo(PreAuthorization::class);
    }

    public function balanceInFavor()
    {
        return $this->hasOne(BalanceInFavor::class);
    }

    public function creditInvoice()
    {
        return $this->hasOne(CreditInvoice::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class)->withTrashed();
    }

    public function invoiceTransaction()
    {
        return $this->hasOne(InvoiceTransaction::class);
    }

    public function services()
    {
        return $this->hasMany(PatientItem::class,'invoice_id','id')->with('item.item_ars')->withTrashed();
    }

    public function service_document()
    {
        return $this->hasOne(InvoiceArsDocument::class, 'service_id');
    }

    public function preAuthorizeItems()
    {
        return $this->hasMany(PreAuthorizationItem::class, 'pre_authorization_id', 'pre_authorization_id')
            ->where('is_paid', true);
    }

}