<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agent_number',
        'name',
        'phone_number',
        'address',
        'status',
        'created_by',
        'updated_by',
    ];

    public function consignments()
    {
        return $this->hasMany(Consignment::class);
    }
}
