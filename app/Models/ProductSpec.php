<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSpec extends Model
{

    protected $table = 'product_specs';
    protected $primaryKey = 'spec_id';
    protected $hidden = ['spec_id'];


    public $timestamps = false;

    protected $fillable = ['type_of_specs', 'value', 'product_id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    
}
