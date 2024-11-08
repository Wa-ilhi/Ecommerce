<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BuyerProductController extends Controller
{
    public function buyerListedProducts(): JsonResponse
    {
        $products = Product::where('status', 'active')
            ->get(['product_name','description','stock_quantity','category', 'price']);

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

    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->input('query'); // Get the search term
        $category = $request->input('category'); // Get the category filter
        $sort = $request->input('sort');

        $ignore_suffices = preg_replace('/(s|es|ing|ed|er|est)$/', '', $searchTerm); //Filter suffices

        if ($ignore_suffices && strlen($ignore_suffices) < 4) {
            return response()->json(['message' => 'Please use a more specific search term.'], 400);
        }

        
        $query = Product::query();

        // Search term query
        if ($searchTerm) {
            $query->where(function($q) use ($ignore_suffices) {
                $q->where('product_name', 'LIKE', "%{$ignore_suffices}%")
                  ->orWhere('description', 'LIKE', "%{$ignore_suffices}%");
            });
        }

        // Filter by category
        if ($category) {
            $query->where('category', $category);
        }

        // Filter by minimum price 
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }

        // Filter by maximum price 
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

         // Sort by creation date 
        if ($sort === 'new') {
            $query->orderBy('created_at', 'desc');
        } elseif ($sort === 'old') {
            $query->orderBy('created_at', 'asc');
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No product found'], 404);
        } 
            $products->makeHidden(['product_id','status']);
        
            $sortingIndicator = $sort === 'new' ? 'Sorted by newest' : 'Sorted by oldest';

            // Prepare the products in a numbered format
            $numberedProducts = $products->map(function ($product, $index) {
                return [
                    'number' => $index + 1,
                    'product' => $product
                ];
            });
        
            return response()->json([
                'message' => $sortingIndicator,
                'products' => $numberedProducts
            ], 200);
          
        
    }

    public function sortProducts(Request $request): JsonResponse
    {
        $sort = $request->input('sort');

        $query = Product::with(['specs', 'media'])->where('status', 'active');

        // Apply 'sort' parameter
        if ($sort === 'new') {
            $query->orderBy('created_at', 'desc');
            $sortingIndicator = 'Sorted by newest';
        } elseif ($sort === 'old') {
            $query->orderBy('created_at', 'asc');
            $sortingIndicator = 'Sorted by oldest';
        } else {
            $sortingIndicator = 'No specific sorting applied';
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No product found'], 404);
        }

        $products->makeHidden(['product_id', 'status']);

        
        $numberedProducts = $products->map(function ($product, $index) {
            return [
                'number' => $index + 1,
                'product' => $product
            ];
        });

        return response()->json([
            'message' => $sortingIndicator,
            'products' => $numberedProducts
        ], 200);
    }

}
