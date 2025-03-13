<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'batch_number',
        'total_amount',
        'discount',
        'payment_method',
        'created_by',
        'updated_by',
    ];

    public function items()
    {
        return $this->hasMany(SalesItem::class, 'sale_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
