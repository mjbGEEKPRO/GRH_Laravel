<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\VerificatioController;
use App\Http\Controllers\forgetPassControlleur;
use App\Http\Controllers\UserInfoControler;
use Illuminate\Support\Facades\Route;


Route::get('/postes', [AuthController::class, 'loadposte']);
Route::post('/verif', [VerificatioController::class, 'verification']);
Route::post('/users', [AuthController::class, 'inscription']);
Route::put('/approuver/{userId}', [AuthController::class, 'approuver']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/verifmeil', [forgetPassControlleur::class, 'verifmeil']);
Route::post('/passReset', [forgetPassControlleur::class, 'passReset']);
Route::get('/getinfo', [UserInfoControler::class, 'getInfo']);
Route::put('/users/{userId}', [UserInfoControler::class, 'update']);
Route::put('/useEdit/{id}', [UserInfoControler::class, 'EditUser']);


// routes/api.php
Route::middleware('auth:api')->get('/check-token', function () {
    return response()->json([
        'success' => true,
    ]);
});

// Route::middleware(['auth'])->group(function () {
//     Route::post('/refresh', [AuthController::class, 'refresh']);
//     Route::get('/me', [AuthController::class, 'me']);
    
// });


