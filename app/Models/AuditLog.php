<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Deshabilita created_at y updated_at

    protected $fillable = [
        'user_id', 'action', 'module', 'old_data', 'new_data', 'ip_address', 'user_agent', 'branch_id'
    ];

    protected $casts = [
        'old_data' => 'json',
        'new_data' => 'json',
    ];

    protected $appends = ['data_old', 'data_new']; 
    protected $hidden = ['old_data', 'new_data']; 
    protected $with = ['user','branch']; 

    public function getDataOldAttribute()
    {
        $data = json_decode($this->old_data);
    
        return $data;
    }

    public function getDataNewAttribute()
    {
        $data = json_decode($this->new_data);
    
        return $data;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
