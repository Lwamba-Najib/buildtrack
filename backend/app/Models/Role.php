<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'roles';
    protected $fillable = [
        'name',
        'environment',
        'created_by',
        'updated_by',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
