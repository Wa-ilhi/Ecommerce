<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories=Category::paginate(10);
        return response()->json($categories,200);
    }

    public function show($id)
    {
      $categories=Category::find($id);
      if($categories){
        return response()->json($categories,200);
      }else return response()->json('Product not found');
    }

    public function store(Request $request)
    {
        try{
            $validated=$request->validate([
                'name' => 'required|unique:category,name',
                'image' => 'required',
            ]);
            $categories= new Category();
            $categories->name=$request->name;
            $categories->save();
            return response()->json('Category has been added',201);
        }catch(Exception $e){
            return response()->json($e,500);
        }
    }
    public function update_category($id,Request $request)
    {
        try{
            $validated = $request->validate([
                'name'=>'required|unique:category,name',
                'image' => 'required',
            ]);
            $categories=Category::find($id);
            if($request->hasFile('image')){
                $path = 'assests/uploads/category/'. $categories->image;
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
               $categories->image = $filename;
            }
            $categories->name=$request->name;
            $categories->update();
            return response()->json('Category Updated',200);
            
        }catch(Exception $e){
            return response()->json($e,500);
        }
    }

    public function delete_category($id)
    {
        $categories=Category::find($id);
        if($categories){
            $categories->delete();
            return response()->json('Category deleted');
        }
        else return response()->json('Category not found');
    }
}
