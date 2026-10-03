<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'consignment_id',
        'product_id',
        'brand_id',
        'measurement_id',
        'batch_number',
        'unit_price',
        'quantity_issued',
        'quantity_sold',
        'quantity_returned',
        'quantity_lost',
        'created_by',
        'updated_by',
    ];

    public function consignment()
    {
        return $this->belongsTo(Consignment::class);
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

    public function reconciliations()
    {
        return $this->hasMany(ConsignmentReconciliation::class);
    }

    // How much of this line is still out with the agent, unaccounted for
    public function getQuantityOutstandingAttribute()
    {
        return $this->quantity_issued
            - $this->quantity_sold
            - $this->quantity_returned
            - $this->quantity_lost;
    }
}
