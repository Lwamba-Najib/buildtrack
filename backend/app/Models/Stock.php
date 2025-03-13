<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $casts = [
        'stock_date' => 'date:Y-m-d', // Ensures the date is in the correct format
    ];
    protected $table = 'stocks';
    protected $fillable = [
        'product_id',
        'brand_id',
        'measurement_id',
        'quantity',
        'unit_price',
        'total_cost',
        'sale_price',
        'min_stock_level',
        'supplier_id',
        'stock_date',
        'environment',
        'created_by',
        'updated_by',
    ];
    // Relationship to Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relationship to Brand
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    // Relationship to Measurement
    public function measurement()
    {
        return $this->belongsTo(Measurement::class, 'measurement_id');
    }

    // Relationship to Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // Relationship to User (created_by)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to User (updated_by)
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
