<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BuyerProductController;
use App\Http\Controllers\VisitorProductController;
use App\Http\Controllers\CategoryController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//User Authentication
//Route::group(['middleware' => 'api','prefix' => 'auth'], function () {
//   Route::post('/login', [AuthController::class, 'login']);
//    Route::post('/register', [AuthController::class, 'register']);
//    Route::post('/logout', [AuthController::class, 'logout']);
//});




// Public/Visitor's Access
Route::prefix('visitor-products')->controller(VisitorProductController::class)->group(function() {
    Route::get('/preshow', 'products');
    Route::get('/showByCategory/{category}', 'showByCategory');
    Route::get('/search', 'search');
});


//Authenticated Seller Routes
Route::group(['middleware' => 'sellerAuth','prefix'=>'seller-products'], function($router){
    Route::controller(ProductController::class)->group(function(){

    Route::get('/listedProducts','products');
    Route::get('/pendingProducts','getPendingProducts');
    Route::get('/show/{product_id}','show');
    Route::post('/store','store');
    Route::post('/update/{product_id}','update');
    Route::put('/approveProduct/{product_id}','approveProduct');
    Route::delete('/destroy/{product_id}','destroy');
    Route::get('/search', 'search');
    Route::get('/showByCategory/{category}', 'showByCategory');
    });
});

//Authenticated Buyer Routes
Route::group(['middleware' => 'buyerAuth','prefix'=>'buyer-products'], function($router){
    Route::controller(BuyerProductController::class)->group(function(){
      
    Route::get('/all','buyerListedProducts');      
    Route::get('/search', 'search');
    Route::get('/sortby', 'sortProducts');
    Route::get('/showByCategory/{category}', 'showByCategory');
    });
});





