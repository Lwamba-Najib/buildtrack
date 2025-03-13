<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'suppliers';
    protected $fillable = [
        'supplier_number',
        'name',
        'phone_number',
        'email',
        'tin',
        'country',
        'address',
        'environment',
        'created_by',
        'updated_by',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
