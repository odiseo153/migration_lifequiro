<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'username',
        'password',
        'position_id',
    ];

    protected $hidden = ['password'];
    protected $appends = ['position_name'];


    // Relación Muchos a Muchos con Empresas
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')->withTimestamps();
    }

    // Relación Muchos a Muchos con Sucursales
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user')->withTimestamps();
    }

    // End of Selection

    // Relación con Posición (Cada usuario tiene solo una posición)
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function role(){
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id');
    }

    public function getPositionNameAttribute(): string
    {
        if (!$this->position) {
            return false;
        }

        return $this->position->name;
    }

    public function waiting_rooms()
    {
        return $this->hasMany(WaitingRoom::class);
    }


    // Reemplazar la función personalizada con la de Spatie
    public function hasPermission($permissionKey, $branchId)
    {
        // Si es super_admin, tiene acceso total
        if ($this->hasRole('super_admin')) {
            return true;
        }


        // Verificar si el usuario tiene el permiso en la sucursal específica
        return $this->permissions()
            ->where('name', $permissionKey)
            ->wherePivot('branch_id','=', $branchId)
            ->exists();
    }

    public function permissionsWithBranch()
{
    return PermissionAssignment::with('permission', 'branch')
        ->where('model_type', self::class)
        ->where('model_id', $this->id)
        ->get();
}
}
