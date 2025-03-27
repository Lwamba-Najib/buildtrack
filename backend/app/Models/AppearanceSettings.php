<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppearanceSettings extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'appearance_settings';
    protected $fillable = [
        'name',
        'logo',
        'favicon',
        'wallpaper',
        'created_by',
        'updated_by',
    ];
}
