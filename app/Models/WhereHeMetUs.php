<?php

namespace App\Models;


class WhereHeMetUs extends BaseModel
{

    protected $fillable = ['name','group_where_met_us_id'];

    public function patients(){
        return $this->hasMany(Patient::class,'where_met_us_id');
    }

    public function group(){
        return $this->belongsTo(GroupWhereMetUs::class,'group_where_met_us_id');
    }
}
