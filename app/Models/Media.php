<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';
    protected $primaryKey = 'media_id';
    protected $hidden = ['media_id'];


    public $timestamps = false;

    protected $fillable = ['file_name','product_id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

}
