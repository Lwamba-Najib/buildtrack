<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'consignment_number',
        'agent_id',
        'status',
        'requested_date',
        'approved_date',
        'approved_by',
        'rejection_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function items()
    {
        return $this->hasMany(ConsignmentItem::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
