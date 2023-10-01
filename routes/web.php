<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/', [MenuController::class, 'index'])->name('menu');

// make a route group for admin
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->middleware('auth');
    Route::get('/login', [AdminController::class, 'login']);
    Route::post('/login', [AdminController::class, 'authenticate']);
});
