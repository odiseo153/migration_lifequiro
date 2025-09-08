<?php

namespace App\Models;


class TypeOfPlan extends BaseModel
{
    protected $fillable = ['name'];
    
    public function plans()
    {
      return $this->hasMany(Plan::class);
    }
}


