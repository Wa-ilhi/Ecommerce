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
        // View paginated products for every visitors'
        if (auth()->check()) {     
            $products = Product::with('specs')->get();
        } else {
            $products = Product::with('specs')->paginate(5);

            // Hide specific attributes for unauthenticated users
            $products->getCollection()->transform(function ($product) {
                $product->makeHidden(['product_id', 'status']); // Hide product_id and status

                // Also hide product_id within each spec
                $product->specs->each(function ($spec) {
                    $spec->makeHidden(['product_id']);
                });

                return $product;
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

    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->input('query'); // Get the search term
        $category = $request->input('category'); // Get the category filter
        $sort = $request->input('sort'); // Get the sorting filter

        $ignore_suffices = preg_replace('/(s|es|ing|ed|er|est)$/', '', $searchTerm); //Filter suffices

        if ($ignore_suffices && strlen($ignore_suffices) < 4) {
            return response()->json(['message' => 'Please use a more specific search term.'], 400);
        }

        // Build the query
        $query = Product::query();

        // Apply search term if provided
        if ($searchTerm) {
            $query->where(function($q) use ($ignore_suffices) {
                $q->where('product_name', 'LIKE', "%{$ignore_suffices}%")
                  ->orWhere('description', 'LIKE', "%{$ignore_suffices}%");
            });
        }

        // Filter Category
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

         // Apply 'sort' parameter
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
}
