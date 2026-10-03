<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'batch_number',
        'total_amount',
        'discount',
        'payment_method',
        'notice',
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
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}