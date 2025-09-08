<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GeneratedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_template_id',
        'user_id',
        'name',
        'filters_applied',
        'status',
        'file_path',
        'file_name',
        'file_size',
        'records_count',
        'started_at',
        'completed_at',
        'error_message',
        'progress_percentage'
    ];

    protected $casts = [
        'filters_applied' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
        'records_count' => 'integer',
        'progress_percentage' => 'integer'
    ];

    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDownloadUrlAttribute(): ?string
    {
        if (!$this->file_path || $this->status !== 'completed') {
            return null;
        }

       // return route('reports.download', $this->id);
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) return '0 B';

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'processing' => 'Procesando',
            'completed' => 'Completado',
            'failed' => 'Fallido',
            default => 'Desconocido'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'secondary'
        };
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /*
    public function fileExists(): bool
    {
        return $this->file_path && Storage::disk('reports')->exists($this->file_path);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($report) {
            // Eliminar archivo físico al eliminar el reporte
            if ($report->file_path && Storage::disk('reports')->exists($report->file_path)) {
                Storage::disk('reports')->delete($report->file_path);
            }
        });
    }
    */
}