<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeleteControler;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\VerificatioController;
use App\Http\Controllers\forgetPassControlleur;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserInfoControler;
use App\Http\Controllers\changePassController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\JsonDataController;
use App\Http\Controllers\settingController;
use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// route public 
Route::get('/postes', [AuthController::class, 'loadposte']);
Route::post('/verif', [VerificatioController::class, 'verification']);
Route::post('/users', [AuthController::class, 'inscription']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:api');;
Route::post('/emeilverif', [forgetPassControlleur::class, 'verifmeil']);
Route::put('/passReset/{id}', [forgetPassControlleur::class, 'passReset']);

//changer le password depuis son espace personnel
Route::patch('/change-password/{userId}', [changePassController::class, 'passReset']);


//setting
Route::patch('/update-personal-info/{id}', [settingController::class, 'personal_info']);
Route::put('/preferences/{id}', [settingController::class, 'preferences']);
Route::put('/compte/{id}', [settingController::class, 'compte']);
//ecriture dans json
Route::apiResource('json-data', JsonDataController::class);


Route::group(['middleware' => 'auth:api'], function () {    
    // Projets
    Route::get('projects', [ProjectController::class, 'index'])->middleware('CheckPermission:projects_view');
    Route::post('newprojects', [ProjectController::class, 'store']);
    Route::get('projects/{id}', [ProjectController::class, 'show']);
    Route::post('projects/{id}/teams', [ProjectController::class, 'createTeams']);
    Route::delete('deleted/{projectId}', [ProjectController::class, 'destroy']);

    //update des info via adin dashboard
    Route::put('/user/{userId}', [UserInfoControler::class, 'update']);
    Route::put('/useEdit/{id}', [UserInfoControler::class, 'EditUser']);
    
    // Tâches
    Route::post('create', [TaskController::class, 'store']);
    Route::patch('tasks/{id}/status', [TaskController::class, 'updateStatus']);
    Route::get('my-tasks', [TaskController::class, 'getMyTasks']);
    Route::delete('delete/{taskId}', [TaskController::class, 'destroy']);
    Route::get('userTast', [TaskController::class, 'userTast']);
    
    // Dashboard
    Route::get('employee-data', [DashboardController::class, 'getEmployeeData']);
    Route::get('my-tasks', [DashboardController::class, 'getMyTasksOnly']);
    Route::get('my-projects', [DashboardController::class, 'getMyProjectsOnly']);
    Route::get('/permission', [UserInfoControler::class, 'getInfo']);
    Route::get('/user', [UserInfoControler::class, 'UserModal']);

    // Admin
    Route::get('admin-data', [DashboardController::class, 'getAdminData']);

    //dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);



});

    Route::put('/approuver/{userId}', [AuthController::class, 'approuver']);




// routes validation rapide du token par react
Route::middleware('auth:api')->get('/check-token', function () {
    return response()->json([
        'success' => true,
    ]);
});




// Routes pour l'historique de connexion (accès admin uniquement)
Route::group(['prefix' => 'admin', 'middleware' => 'auth:api'], function () {
    Route::get('/connection-history', [DashboardController::class, 'getConnectionHistory']);
    Route::get('/connection-history/export', [DashboardController::class, 'exportConnectionHistory']);
});


//Route pour supprimer les teams

// Supprimer plusieurs équipes à la fois
Route::delete('/projects/{projectId}', [DeleteControler::class, 'deleteTeams']);

// Route::middleware(['auth'])->group(function () {
//     Route::post('/refresh', [AuthController::class, 'refresh']);
//     Route::get('/me', [AuthController::class, 'me']);
    
// });


//Route pour recupéer les permissions des users
Route::middleware('auth:api')->get('/user/permissions', function (Request $request) {
    $user = $request->user();
    Log::info("arriver demande ". $user);
    // Charger les permissions de l'utilisateur avec leurs détails
    $permissions = $user->permissions()
        ->select('permissions.id', 'permissions.nom', 'permissions.slug', 'permissions.description')
        ->get();
    $departement = null;
            if ($user->role && $user->role->departement_id) {
                $departement = Departement::find($user->role->departement_id);
            }
    return response()->json([
        'success' => true,
        'permissions' => $permissions,
        'user' => [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'nom' => $user->nom,
            'email' => $user->email,
            'departement' =>  $departement ? $departement->nom : null,
        ]
    ]);
});


//route pour les département

Route::middleware('auth:api')->group(function () {
    // Routes CRUD départements
    Route::get('/departements', [DepartementController::class, 'index']);
    Route::get('/departements/{id}', [DepartementController::class, 'show']);
    Route::post('/departements', [DepartementController::class, 'store']);
    Route::put('/departements/{id}', [DepartementController::class, 'update']);
    Route::delete('/departements/{id}', [DepartementController::class, 'destroy']);
    
    // Route utilitaire pour vérifier l'existence d'un poste
    Route::post('/postes/check-exists', [DepartementController::class, 'checkPosteExists']);
});
