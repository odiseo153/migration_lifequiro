<?php

// app/Models/PermissionAssignment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionAssignment extends Model
{
    protected $table = 'model_has_permissions';
    public $timestamps = false;
    protected $fillable = ['permission_id', 'model_type', 'model_id', 'branch_id'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function permission()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Permission::class, 'permission_id');
    }
}

