<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecuritySettings extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'security_settings';
    protected $fillable = [
        'security_settings_2fa',
        'security_settings_lowercase',
        'security_settings_uppercase',
        'security_settings_numbers',
        'security_settings_symbols',
        'security_settings_length',
        'security_settings_expiry',
        'dormant_account_expiry',
        'security_settings_login_attempt',
        'security_settings_history_counts',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
