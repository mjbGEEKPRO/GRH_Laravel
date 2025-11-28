<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

   public function userTast()
   {
    $role=Role::whereIn("nom", ["Administrateur","Manager"])->pluck('id');
    $users=User::where("statut",true)->whereNotIn('role_id', $role)->get();
    return response()->json([
            'success' => true,
            'users' => $users
        ], 201);
   }
    public function store(Request $request)
    {
        try{
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'projet_id' => 'nullable|exists:projects,id',
            'assigne_a_user_id' => 'required|exists:users,id',
            'date_echeance' => 'required|date',
            'priorite' => 'required|in:Basse,Normale,Haute'
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        $assignedUser = User::find($request->assigne_a_user_id);
        
        $task = Task::create([
            ...$request->only(['titre', 'description', 'projet_id', 'assigne_a_user_id', 'date_echeance', 'priorite']),
            'created_by' => $user->id
        ]);

        // Charger les relations pour l'email
        $task->load(['project', 'assignedUser', 'creator']);
        $newdata = Task::with(['project', 'assignedUser', 'creator'])->get();
        
        return response()->json([
            'success' => true,
            'message' => "Tâche créée avec succès !",
            'tasks' => $newdata,
            // Données pour l'email
            'email_data' => [
                'assigned_user' => [
                    'email' => $assignedUser->email_personnel ?? $assignedUser->email,
                    'nom' => $assignedUser->prenom . ' ' . $assignedUser->nom
                ],
                'task' => [
                    'titre' => $task->titre,
                    'description' => $task->description,
                    'priorite' => $task->priorite,
                    'date_echeance' => $task->date_echeance
                ],
                'creator' => $user->prenom . ' ' . $user->nom,
                'project' => $task->project ? ['nom' => $task->project->nom] : null
            ]
        ], 201);
     }catch (\Exception $e) {
        Log::error('Erreur lors de la creation de la tache:', [
            'message' => "Erreur lors de la creation de la tâche ",
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'data' => $request->all() ,
            'errors' => $e->getMessage()

        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la création de la tache.',
            'debug_info' => config('app.debug') ? [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ] : null
        ], 500);
    }}

    public function updateStatus(Request $request, $taskId)
    {
        try {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|string|in:A faire,En cours,En attente,Terminé'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Statut invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $task = Task::find($taskId);
        
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée'
            ], 404);
        }
        Log::info("stut task ".$task->statut );
        // Vérification : empêcher la modification d'une tâche déjà terminée
        if ($task->statut === 'Terminé') {
            return response()->json([
                'success' => false,
                
                'message' => 'Impossible de modifier le statut d\'une tâche déjà terminée'
            ], 403);
        }        
        

        $oldStatus = $task->statut;
        $task->statut = $request->statut;
        $task->save();

        return response()->json([
            'success' => true,
            'message' => "Statut mis à jour de '{$oldStatus}' vers '{$request->statut}'",
            'data' => $task
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur lors de la mise à jour du statut de la tâche:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'task_id' => $taskId,
            'data' => $request->all()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la mise à jour du statut'
        ], 500);
    }
}

    public function getMyTasks()
    {
        $tasks = Task::with(['project', 'creator'])
                    ->where('assigne_a_user_id', JWTAuth::parseToken()->authenticate()->id)
                    ->orderBy('date_echeance')
                    ->get();

        return response()->json([
            'success' => true,
            'message'=>"Le statut de votre tache a été mis a jour",
            'data' => $tasks
        ]);
    }

    // Méthodes d'autorisation
    private function isManager($user)
    {
        return str_contains($user->poste, 'Manager') || str_contains($user->poste, 'Chef');
    }

    private function canAssignTask($user, $assignedUser)
    {
        if ($user->departement === 'Administration') return true;
        if (!$this->isManager($user)) return false;
        
        return $assignedUser->departement === $user->departement;
    }

    private function canUpdateTask($user, $task)
    {
        return $task->assigne_a_user_id === $user->id || 
               $user->departement === 'Administration' ||
               $task->created_by === $user->id ||
               ($this->isManager($user) && $task->assignedUser->departement === $user->departement);
    }

    public function destroy($id)
{
    try {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée'
            ], 404);
        }

        $user = JWTAuth::user();

        // Vérifier les permissions
        if ($user->departement !== 'Administration' && 
            $task->created_by !== $user->id &&
            (!str_contains($user->poste, 'Manager') || 
             $task->assignedUser->departement !== $user->departement)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour supprimer cette tâche'
            ], 403);
        }

        $task->delete();
        $newdata=Task::with(['project', 'assignedUser', 'creator'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Tâche supprimée avec succès',
            'tasks' => $newdata
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression de la tâche',
            'error' => $e->getMessage()
        ], 500);
    }
}
}