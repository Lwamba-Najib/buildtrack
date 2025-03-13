<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'application_logs';
    protected $fillable = [
        'type',
        'activity',
        'details',
        'user_agent',
        'browser',
        'platform',
        'ip',
        'mac_address',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
