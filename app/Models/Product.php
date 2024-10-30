<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    
    protected $table = 'products';
    protected $primaryKey = 'product_id'; 

    public $incrementing = false; 

    protected $fillable = [
          
        'product_name',
        'description',
        'price',
        'stock_quantity',
        'category', 
        'status',    
    ];

    // Hidden attributes
    protected $hidden = [
        'created_at',
        'updated_at', 
    ];

    
    public static function getCategories()
    {
        return ['shorts', 'pants', 't-shirts', 'shoes', 'hats']; 
    }

 

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->product_id = (string) Str::uuid(); // Generate a UUID
        });
    }

    public function specs()
    {
        return $this->hasMany(ProductSpec::class, 'product_id','product_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'product_id','product_id');
    }

}
