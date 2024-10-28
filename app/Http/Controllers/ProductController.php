<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; 


class ProductController extends Controller
{
    public function products(): JsonResponse
    {
        $products = Product::with(['specs','media'])->get(); // Retrieve all products
        return response()->json($products);
    }


    public function show($product_id): JsonResponse
    {
        $product = Product::with(['specs','media'])->find($product_id);

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
            'file_name' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        
        $product = Product::create($request->all());

      
        ProductSpec::create([
            'product_id' => $product->product_id,
            'type_of_specs' => $request->type_of_specs,
            'value' => $request->value,
        ]);

        if ($request->hasFile('file_name')) {
            $file = $request->file('file_name');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs( 'file_name', $filename, 'public');

            Media::create([
                'file_name' => $filePath,
                'product_id' => $product->product_id,
            ]);
        }

        $product->load('specs', 'media');
        return response()->json($product, 201); // Return the created product with specs
    }
    

    public function update(Request $request, $product_id): JsonResponse
    {
        $product = Product::with('specs','media')->find($product_id);
    
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
            'file_name' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',

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

        // Handle media update
        if ($request->hasFile('file_name')) {
            // Delete old media file if it exists
            if ($product->media->isNotEmpty()) {
                $product->media->each(function ($media) {
                    Storage::disk('public')->delete($media->file_name);
                    $media->delete();
                });
            }
    
            // Store new media file
            $file = $request->file('file_name');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('file_name', $filename, 'public');
    
            // Create new media entry
            Media::create([
                'file_name' => $filePath,
                'product_id' => $product->product_id,
            ]);
        }
    
        // Reload the product with specs
        $product->load('specs','media');
        return response()->json($product);
    }
    
    
    

    public function destroy($product_id): JsonResponse
    {
        $product = Product::with('media', 'specs')->find($product_id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        // Check if there are media files and delete each from storage
        if ($product->media->isNotEmpty()) {
            foreach ($product->media as $media) {
                if (\Storage::disk('public')->exists($media->file_name)) {
                    \Storage::disk('public')->delete($media->file_name);
                }
                $media->delete(); 
            }
        }
    
   
        $product->specs()->delete();
        $product->delete();
    
        return response()->json(['message' => 'Product and its specifications deleted successfully']);
    }
    

    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->input('query'); // Get the search term
        $category = $request->input('category'); // Get the category filter

        $singularTerm = preg_replace('/(s|es|ing|ed|er|est)$/', '', $searchTerm); //Filter suffixes

        if (strlen($singularTerm) < 4) {
            return response()->json(['message' => 'Please use a more specific search term.'], 400);
        }

        // Build the query
        $query = Product::query();

        // Apply search term if provided
        if ($searchTerm) {
            $query->where(function($q) use ($singularTerm) {
                $q->where('product_name', 'LIKE', "%{$singularTerm}%")
                  ->orWhere('description', 'LIKE', "%{$singularTerm}%");
            });
        }

        // Apply category filter if provided
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
        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No product found'], 404);
        } else {
            return response()->json($products, 200);
        }
    }

    

}
