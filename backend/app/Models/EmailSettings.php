<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailSettings extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'email_settings';
    protected $fillable = [
        'sender_name',
        'sender_email',
        'smtp_auth',
        'smtp_host',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_port',
        'created_by',
        'updated_by',
    ];
}
