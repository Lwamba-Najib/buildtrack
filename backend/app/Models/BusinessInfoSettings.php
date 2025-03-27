<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessInfoSettings extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'business_info_settings';
    protected $fillable = [
        'business_name',
        'business_reg_number',
        'business_tin',
        'business_slogan',
        'business_address',
        'business_email',
        'business_contact',
        'business_website',
        'business_legal_disclaimer',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

