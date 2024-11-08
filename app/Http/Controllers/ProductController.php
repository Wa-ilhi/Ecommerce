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
        $products = Product::with(['specs', 'media'])
        ->where('status', 'active')
        ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No active products found'], 404);
        }

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

        if ($request->isMethod('post')) {
            $request->setMethod('PUT');
        }
        
        $product = Product::with('specs','media')->find($product_id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Validation rules
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

        // Update the text fields that are provided in the request
        foreach (['product_name', 'description', 'price', 'stock_quantity', 'category'] as $field) {
            if ($request->filled($field)) {
                $product->$field = $request->input($field);
            }
        }
        $product->save();

        // Check for specifications update
        if ($request->filled('type_of_specs') && $request->filled('value')) {
            $specs = $product->specs;
            if ($specs->isEmpty()) {
                ProductSpec::create([
                    'product_id' => $product->product_id,
                    'type_of_specs' => $request->input('type_of_specs'),
                    'value' => $request->input('value'),
                ]);
            } else {
                $specs->first()->update([
                    'type_of_specs' => $request->input('type_of_specs'),
                    'value' => $request->input('value'),
                ]);
            }
        }

        // Handle media update
        if ($request->hasFile('file_name')) {
            if ($product->media->isNotEmpty()) {
                $product->media->each(function ($media) {
                    Storage::disk('public')->delete($media->file_name);
                    $media->delete();
                });
            }

            // Store new media file
            $file = $request->file('file_name');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('media', $filename, 'public');

            // Create new media entry
            Media::create([
                'file_name' => $filePath,
                'product_id' => $product->product_id,
            ]);
        }

        // Reload the product with specs
        $product->load('specs', 'media');
        return response()->json($product);
    }

    
    
    

    public function destroy($product_id): JsonResponse
    {
        $product = Product::with('media', 'specs')->find($product_id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        // Checks if there are media files and delete each from storage
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


    // Get all pending products
    public function getPendingProducts(): JsonResponse
    {
        $pendingProducts = Product::with(['specs', 'media'])
                            ->where('status', 'pending')
                            ->get();

        if ($pendingProducts->isEmpty()) {
            return response()->json(['message' => 'No pending products found'], 404);
        }

        return response()->json($pendingProducts);
    }


    // Approve a Product
    public function approveProduct($product_id): JsonResponse
    {
        $product = Product::find($product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->status = 'active';
        $product->save();

        return response()->json(['message' => 'Product published successfully']);
    }


    

}
