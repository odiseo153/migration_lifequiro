<?php

namespace App\Models;


class GroupWhereMetUs extends BaseModel
{
    protected $fillable = ['name'];

    public function where_he_met_us(){
        return $this->hasMany(WhereHeMetUs::class);
    }
}

