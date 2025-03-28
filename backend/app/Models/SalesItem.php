<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'brand_id',
        'measurement_id',
        'batch_number',
        'quantity',
        'unit_price',
        'total_price',
        'created_by',
        'updated_by',
    ];

    public function sale()
    {
        return $this->belongsTo(Sales::class, 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function measurement()
    {
        return $this->belongsTo(Measurement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
