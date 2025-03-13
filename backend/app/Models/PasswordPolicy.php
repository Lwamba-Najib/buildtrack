<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordPolicy extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'password_policies';
    protected $fillable = [
        'password_policy_pwd',
        'password_policy_due',
        'user_id',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
