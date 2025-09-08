<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'month',
        'day',
        'description',
        'is_active',
    ];

    protected $casts = [
        'month' => 'integer',
        'day' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Scope para obtener solo los días festivos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para obtener días festivos por fecha (mes y día)
     */
    public function scopeByDate($query, $date)
    {
        $carbonDate = \Carbon\Carbon::parse($date);
        return $query->where('month', $carbonDate->month)
                    ->where('day', $carbonDate->day);
    }

    /**
     * Scope para obtener días festivos por mes y día específicos
     */
    public function scopeByMonthAndDay($query, $month, $day)
    {
        return $query->where('month', $month)->where('day', $day);
    }

    /**
     * Scope para obtener días festivos de un mes específico
     */
    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Verifica si una fecha específica es un día festivo activo
     */
    public static function isHoliday($date)
    {
        $carbonDate = \Carbon\Carbon::parse($date);
        return self::active()
            ->byMonthAndDay($carbonDate->month, $carbonDate->day)
            ->exists();
    }

    /**
     * Obtiene los días festivos activos ordenados por mes y día
     */
    public static function getCurrentYearHolidays()
    {
        return self::active()
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }

    /**
     * Obtiene la fecha formateada para mostrar (sin año)
     */
    public function getFormattedDateAttribute()
    {
        return sprintf('%02d-%02d', $this->month, $this->day);
    }

    /**
     * Obtiene la fecha completa para el año actual
     */
    public function getDateForCurrentYearAttribute()
    {
        return \Carbon\Carbon::create(now()->year, $this->month, $this->day);
    }

    /**
     * Obtiene la fecha completa para un año específico
     */
    public function getDateForYear($year)
    {
        return \Carbon\Carbon::create($year, $this->month, $this->day);
    }
} 