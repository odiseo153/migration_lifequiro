<?php

namespace App\Models;



class Company extends BaseModel
{

    protected $fillable = [
        'name',
        'logo_horizontal',
        'isotipo', 
        'rnc', 
        'status', 
        'user_id', 
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class,'company_user')->withTimestamps();
    }

    public function owner()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, Branch::class);
    }

    public function setLogoHorizontalAttribute($value)
    {
        $this->handleFileAttribute('logo_horizontal', $value, 'companies/'.$this->name.'-'.$this->id);
    }

    public function setIsotipoAttribute($value)
    {
        $this->handleFileAttribute('isotipo', $value, 'companies/'.$this->name.'-'.$this->id);
    }

}
