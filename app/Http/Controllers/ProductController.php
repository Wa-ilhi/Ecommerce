<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products=Product::paginate(10);
        if($products){
            return response()->json($products,200);

        }else return response()->json('No Products');
    }

    public function show($id)
    {
      $products=Product::find($id);
      if($products){
        return response()->json($products,200);
      }else return response()->json('Product not found');
    }

    public function store(Request $request)
    {
       
             Validator::make($request->all(),[
                'product_name'=>'required|string|max:255',
                'description'=>'required|string',
                'price'=>'required|numeric',
                'stock_quantity'=>"required|numeric",
                'category_id'=>'required|integer|exists:category,id',
             ]);
            $products= new Product();
            $products->product_name=$request->product_name;
            $products->price=$request->price;
            $products->description=$request->description;
            $products->stock_quantity=$request->stock_quantity;
            $products->category_id=$request->category_id;
            $products->save();
            if($request->hasFile('image')){
                $path = 'assests/uploads/category/'. $products->image;
               if (File::exists($path)){
                File::delete($path);
               }
               $file = $request->file('image');
               $ext = $file->getClientOriginalExtension();
               $filename = time() . '.' . $ext;
               
               try{
                $file->move('assets/uploads/category', $filename);
               }catch(FileException $e){
                dd($e);
               }
               $products->image = $filename;
            }
            $products->save();
            return response()->json('Product Added',201);
    }

    public function update($id,Request $request)
    {
       Validator::make($request->all(),[
                'category_id'=>'required|numeric',
                'product_name'=>'required',
                'price'=>'required|numeric',
                'description'=>'required',
                'stock_quantity'=>"required|numeric",
             ]);
             if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422); // Validation failed
            }
            
            $products=Product::find($id);
            if($products){
            $products->product_name=$request->product_name;
            $products->price=$request->price;
            $products->description=$request->description;
            $products->stock_quantity=$request->stock_quantity;
            $products->category_id=$request->category_id;
            $products->save();
            if($request->hasFile('image')){
                $path = 'assests/uploads/category/'. $products->image;
               if (File::exists($path)){
                File::delete($path);
               }
               $file = $request->file('image');
               $ext = $file->getClientOriginalExtension();
               $filename = time() . '.' . $ext;
               
               try{
                $file->move('assets/uploads/category', $filename);
               }catch(FileException $e){
                dd($e);
               }
               $products->image = $filename;
            }
            try{
            $products->save();
            return response()->json('Product Updated',201);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add product: ' . $e->getMessage()], 500);
        }
    }
    }


    public function destroy($id)
    {
        $products=Product::find($id);
        if($products){
            $products->delete();
            return response()->json('Product deleted');
        }
        else return response()->json('Product not found');
    }
}
