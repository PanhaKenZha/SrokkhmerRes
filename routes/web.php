<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestaurantController;

Route::get('/', [RestaurantController::class,'menu']);
Route::view('/about','about');
Route::view('/vision','vision');
Route::view('/faq','faq');
Route::get('/contact',function(){return view('contact',['restaurants'=>\App\Models\Restaurant::where('is_active',true)->orderBy('name')->get()]);});
Route::get('/fast-food',[\App\Http\Controllers\FastFoodController::class,'index']);
Route::get('/restaurants',[\App\Http\Controllers\ManagementController::class,'directory']);
Route::get('/restaurants/{restaurant}',[\App\Http\Controllers\ManagementController::class,'show'])->whereNumber('restaurant');
Route::get('/maps/restaurants',[\App\Http\Controllers\DeliveryController::class,'nearby'])->middleware('throttle:10,1');
Route::post('/delivery/quote',[\App\Http\Controllers\DeliveryController::class,'quote'])->middleware('throttle:10,1')->block(10,10);
Route::get('/orders/{reference}',function(string $reference){
    return view('tracking',['order'=>\App\Models\Order::where('reference',$reference)->firstOrFail()]);
})->whereUuid('reference')->middleware('throttle:30,1');
Route::post('/orders',[RestaurantController::class,'store'])->middleware('throttle:20,1')->block(10,10);
Route::middleware('guest')->group(function () {
    Route::get('/login',[\App\Http\Controllers\AccountController::class,'showLogin'])->name('login');
    Route::post('/login',[RestaurantController::class,'login'])->middleware('throttle:5,1');
    Route::get('/register',[\App\Http\Controllers\AccountController::class,'showRegister']);
    Route::post('/register',[\App\Http\Controllers\AccountController::class,'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::get('/account/orders',[\App\Http\Controllers\AccountController::class,'orders']);
    Route::middleware('can:restaurants.manage')->group(function () {
        Route::get('/staff/restaurants',[\App\Http\Controllers\ManagementController::class,'restaurants']);
        Route::post('/staff/restaurants',[\App\Http\Controllers\ManagementController::class,'saveRestaurant']);
        Route::get('/staff/restaurants/{restaurant}/edit',[\App\Http\Controllers\ManagementController::class,'editRestaurant']);
        Route::patch('/staff/restaurants/{restaurant}',[\App\Http\Controllers\ManagementController::class,'saveRestaurant']);
    });
    Route::middleware('can:menu.manage')->group(function () {
        Route::get('/staff/menu',[\App\Http\Controllers\ManagementController::class,'menu']);
        Route::post('/staff/menu',[\App\Http\Controllers\ManagementController::class,'saveMenu']);
        Route::get('/staff/menu/{item}/edit',[\App\Http\Controllers\ManagementController::class,'menu']);
        Route::patch('/staff/menu/{item}',[\App\Http\Controllers\ManagementController::class,'saveMenu']);
    });
    Route::middleware('can:orders.manage')->group(function () {
        Route::get('/staff',[RestaurantController::class,'dashboard'])->name('manager.dashboard');
        Route::patch('/staff/orders/{order}',[RestaurantController::class,'update']);
    });
    Route::middleware('can:banners.manage')->group(function () {
        Route::get('/staff/banners',[\App\Http\Controllers\BannerController::class,'index']);
        Route::post('/staff/banners',[\App\Http\Controllers\BannerController::class,'store']);
        Route::get('/staff/banners/{banner}/edit',[\App\Http\Controllers\BannerController::class,'edit']);
        Route::patch('/staff/banners/{banner}',[\App\Http\Controllers\BannerController::class,'update']);
        Route::patch('/staff/banners/{banner}/move',[\App\Http\Controllers\BannerController::class,'move']);
        Route::delete('/staff/banners/{banner}',[\App\Http\Controllers\BannerController::class,'destroy']);
    });
    Route::middleware('can:users.manage')->group(function () {
        Route::get('/staff/team',[\App\Http\Controllers\TeamController::class,'index']);
        Route::post('/staff/team',[\App\Http\Controllers\TeamController::class,'store']);
        Route::patch('/staff/team/{user}',[\App\Http\Controllers\TeamController::class,'update']);
    });
    Route::post('/logout',function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    });
});
