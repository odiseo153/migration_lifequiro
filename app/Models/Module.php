<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = ['module_key', 'name'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'module_permission', 'module_id', 'permission_id')->withTimestamps();
    }
}