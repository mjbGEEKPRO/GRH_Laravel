<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::get('/users', [AuthController::class, 'inscription']);

// Route::post('/users', [AuthController::class, 'inscription']);