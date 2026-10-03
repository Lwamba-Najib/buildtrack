<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'consignment_item_id',
        'quantity_sold',
        'quantity_returned',
        'quantity_lost',
        'cash_collected',
        'reconciled_date',
        'created_by',
    ];

    public function consignmentItem()
    {
        return $this->belongsTo(ConsignmentItem::class);
    }
}
