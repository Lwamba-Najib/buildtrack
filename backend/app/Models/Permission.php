<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;
    protected $table = 'permissions';
    protected $fillable = [
        'role_id',
        'menu',
        'created_by',
        'updated_by',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
