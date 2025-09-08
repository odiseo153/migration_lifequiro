<?php

namespace App\Models;


class TypeOfItem extends BaseModel
{
    protected $fillable = ['name'];

    protected $table = 'types_of_items';
   
    public function items()
    {
        return $this->hasMany(Item::class);
    }

}
