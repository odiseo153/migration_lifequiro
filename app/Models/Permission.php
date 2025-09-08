<?php
namespace App\Models;


class Permission extends BaseModel
{

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = ['permission_key', 'name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'id', 'role_id')->withTimestamps();
    }
}