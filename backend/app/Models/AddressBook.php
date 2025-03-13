<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressBook extends Model
{
    use HasFactory;

    protected $table = 'address_books';
    protected $fillable = [
        'name',
        'phone_number',
        'created_by',
        'updated_by',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
