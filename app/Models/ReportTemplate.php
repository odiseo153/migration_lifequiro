<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'fields',
        'filters',
        'template_path',
        'export_format',
        'active'
    ];

    protected $casts = [
        'fields' => 'array',
        'filters' => 'array',
        'active' => 'boolean'
    ];

    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }

    public function getFieldsListAttribute(): array
    {
        return $this->fields ?? [];
    }

    public function getAvailableFiltersAttribute(): array
    {
        return $this->filters ?? [];
    }
}

