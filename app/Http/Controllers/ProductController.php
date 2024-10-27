<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function products(): JsonResponse
    {
        $products = Product::with('specs')->get(); // Retrieve all products
        return response()->json($products);
    }

    public function show($product_id): JsonResponse
    {
        $product = Product::with('specs')->find($product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function showByCategory($category): JsonResponse
    {
        
        if (!in_array($category, ['shorts', 'pants', 't-shirts', 'shoes', 'hats'])) {
            return response()->json(['message' => 'Invalid category'], 400);
        }

        $products = Product::where('category', $category)->get();

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
      
        $request->validate([
            'product_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
            'category' => 'required|in:shorts,pants,t-shirts,shoes,hats',
            'type_of_specs' => 'required|string|max:100',
            'value' => 'required|string|max:255',
        ]);

        
        $product = Product::create($request->all());

      
        ProductSpec::create([
            'product_id' => $product->product_id,
            'type_of_specs' => $request->type_of_specs,
            'value' => $request->value,
        ]);

        $product->load('specs');
        return response()->json($product, 201); // Return the created product with specs
    }
    

    public function update(Request $request, $product_id): JsonResponse
    {
        $product = Product::with('specs')->find($product_id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        $request->validate([
            'product_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'stock_quantity' => 'nullable|integer',
            'category' => 'nullable|in:shorts,pants,t-shirts,shoes,hats',
            'type_of_specs' => 'nullable|string|max:100',
            'value' => 'nullable|string|max:255',
        ]);
    
        // Update product details
        $product->update($request->only(['product_name', 'description', 'price', 'stock_quantity']));
    
        // Check for specifications in the request
        if ($request->has('type_of_specs') && $request->has('value')) {
            // Check if there are existing specifications
            $specs = $product->specs;
    
            if ($specs->isEmpty()) {
                // If no specifications exist, create a new one
                ProductSpec::create([
                    'product_id' => $product->product_id,
                    'type_of_specs' => $request->input('type_of_specs'),
                    'value' => $request->input('value'),
                ]);
            } else {
                // If specifications exist, update the first one
                $specs->first()->update([
                    'type_of_specs' => $request->input('type_of_specs'),
                    'value' => $request->input('value'),
                ]);
            }
        }
    
        // Reload the product with specs
        $product->load('specs');
        return response()->json($product);
    }
    
    
    

    public function destroy($product_id): JsonResponse
    {
        $product = Product::find($product_id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        $product->specs()->delete();
        $product->delete();
    
        return response()->json(['message' => 'Product and its specifications deleted successfully']);
    }
    

    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->input('query'); // Get the search term
        $category = $request->input('category'); // Get the category filter

        // Build the query
        $query = Product::query();

        // Apply search term if provided
        if ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('product_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply category filter if provided
        if ($category) {
            $query->where('category', $category);
        }

        // Filter by minimum price only if it’s provided
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }

        // Filter by maximum price only if it’s provided
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }
        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No product found'], 404);
        } else {
            return response()->json($products, 200);
        }
    }

    

}
