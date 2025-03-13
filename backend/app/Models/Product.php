<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'products';
    protected $fillable = [
        'name',
        'category_id',
        'environment',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class, 'product_id');
    }
}
