<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PackController;
use App\Http\Controllers\TaskController;
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
Route::post('/game', [GameController::class, 'start']);

 // make a route group for admin
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::get('/login', [AdminController::class, 'login'])->withoutMiddleware('auth');
    Route::post('/login', [AdminController::class, 'authenticate'])->withoutMiddleware('auth');
    Route::get('/kofi', [AdminController::class, 'kofi']);

    Route::resource('packs',PackController::class);
    Route::resource('tasks', TaskController::class);
});

