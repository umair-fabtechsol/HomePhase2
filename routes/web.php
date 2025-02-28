<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('form', [App\Http\Controllers\AuthController::class, 'form'])->name('form');
Route::post('MediaUpload', [App\Http\Controllers\ServiceProviderController::class, 'MediaUpload'])->name('TestMediaUpload');