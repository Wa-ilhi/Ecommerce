<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_name',
        'description',
        'price',
        'stock_quantity',
    ];

    protected $hidden = [
        'created_at',
        'update_at'
    ];

    public function category(){
        return $this->belongsTo(category::class,'category_id');
    }

}
