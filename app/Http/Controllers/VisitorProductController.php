<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class VisitorProductController extends Controller
{
    public function products(): JsonResponse
    {
        if (auth()->check()) {
          
            $products = Product::with('specs')->get();
        } else {
   
            $products = Product::with('specs')->paginate(5); 
            
            // Hide specific attributes for unauthenticated users
            $products->getCollection()->transform(function ($product) {
                return $product->makeHidden(['product_id','status']); 
            });
        }

        
        return response()->json($products);
    }

    //under processing pa
    public function showByCategory($category): JsonResponse
    {
        
        if (!in_array($category, ['shorts', 'pants', 't-shirts', 'shoes', 'hats'])) {
            return response()->json(['message' => 'Invalid category'], 400);
        }

        $products = Product::where('category', $category)->get();

        return response()->json($products);
    }
}
