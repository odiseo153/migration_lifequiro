<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelHelperTrait;

class BaseModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = [ 'updated_at'];

    public function getFormattedCreatedAt()
    {
        return $this->created_at->translatedFormat('l j \d\e F Y \a \l\a\s H:i');
    }

    public function getFormattedUpdatedAt()
    {
        return $this->updated_at->translatedFormat('l j \d\e F Y \a \l\a\s H:i');
        }

    /*
    protected static function bootSoftDeletes()
    {
        // No hacer nada: evita que se aplique el global scope de SoftDeletes
    }

    */
    protected static function boot()
    {
        parent::boot();

        // Agregar orden por defecto
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('id','desc');
        });
    }

    public function scopeName($query, $name)
    {
        return $query->where('name', 'ilike', '%' . $name . '%');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim(strtolower($value));
    }

    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }
}




