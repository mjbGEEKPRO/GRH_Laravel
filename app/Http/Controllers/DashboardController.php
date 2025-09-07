<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getEmployeeData()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $myTasks = Task::with(['project'])
                      ->where('assigne_a_user_id', $user->id)
                      ->get();

        $projectIds = $myTasks->pluck('projet_id')->filter()->unique();
        $myProjects = Project::whereIn('id', $projectIds)->get();

        $stats = [
            'totalTasks' => $myTasks->count(),
            'completedTasks' => $myTasks->where('statut', 'Terminé')->count(),
            'inProgressTasks' => $myTasks->where('statut', 'En cours')->count(),
            'pendingTasks' => $myTasks->where('statut', 'A faire')->count(),
            'overdueTasks' => $myTasks->filter(fn($task) => $task->is_overdue)->count(),
            'projectsCount' => $myProjects->count()
        ];
        Log::info("data Task ".$myTasks);
        Log::info("data project ".$myProjects);
        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $myTasks,
                'projects' => $myProjects,
                'stats' => $stats
            ]
        ]);
    }

    public function getAdminData()
    {
        $data = [
            'projets' => Project::with(['tasks', 'creator'])->get(),
            'task' => Task::with(['project', 'assignedUser', 'creator'])->get(),
            'user' => User::where('statut', true)->get(),
            
        ];
        Log::info("task satut ".  $data['task'] );
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }


public function getMyTasksOnly()
{
    try {
        $user = JWTAuth::user();
        $tasks = Task::with(['project', 'creator'])
                    ->where('assigne_a_user_id', $user->id)
                    ->orderBy('date_echeance', 'asc')
                    ->get();

        // Ajouter les informations calculées
        $tasks->each(function ($task) {
            $task->is_overdue = $task->is_overdue;
            $task->days_until_deadline = $task->days_until_deadline;
        });

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des tâches',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getMyProjectsOnly()
{
    try {
        $user = JWTAuth::user();
        
        // Récupérer tous les projets où j'ai des tâches
        $myProjects = Project::whereHas('tasks', function($q) use ($user) {
                                $q->where('assigne_a_user_id', $user->id);
                            })
                            ->with(['tasks' => function($q) use ($user) {
                                $q->where('assigne_a_user_id', $user->id)
                                  ->with('creator');
                            }])
                            ->get();

        // Calculer la progression et renommer les relations
        $myProjects->each(function ($project) {
            $project->progression = $project->progress_percentage;
            $project->myTasks = $project->tasks;
            unset($project->tasks); // Supprimer l'ancienne clé
        });

        return response()->json([
            'success' => true,
            'data' => $myProjects
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des projets',
            'error' => $e->getMessage()
        ], 500);
    }
}



 public function getConnectionHistory(Request $request)
{
    // Vérifier que l'utilisateur est admin
    $user = JWTAuth::parseToken()->authenticate();
    
    Log::info("user ". $user);
    if ($user->role && $user->role->departement_id) {
                $departement = Departement::find($user->role->departement_id);
            }

    // if ($departement !== 'Administration') {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Accès non autorisé'
    //     ], 403);
    // }

    $request->validate([
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date|after_or_equal:date_from',
        'user_id' => 'nullable|integer|exists:users,id',
        // 'departement' => 'nullable|string',
        'per_page' => 'nullable|integer|min:1|max:100'
    ]);

    $query = DB::table('user_connections')
                ->join('users', 'user_connections.user_id', '=', 'users.id')
                ->select([
                    'user_connections.id',
                    'users.nom',
                    'users.prenom',
                    'users.email_pro',
                    // 'users.departement',
                    // 'users.role',
                    'user_connections.login_at',
                    'user_connections.logout_at',
                    'user_connections.ip_address',
                    'user_connections.user_agent',
                    'user_connections.session_duration'
                ])
                ->orderBy('user_connections.login_at', 'desc');

    // Filtres
    if ($request->date_from) {
        $query->whereDate('user_connections.login_at', '>=', $request->date_from);
    }

    if ($request->date_to) {
        $query->whereDate('user_connections.login_at', '<=', $request->date_to);
    }

    if ($request->user_id) {
        $query->where('users.id', $request->user_id);
    }

    if ($request->departement) {
        $query->where('users.departement', $request->departement);
    }

    // Si pas de filtre de date, limiter aux 30 derniers jours par défaut
    if (!$request->date_from && !$request->date_to) {
        $query->where('user_connections.login_at', '>=', now()->subDays(30));
    }

    $perPage = $request->per_page ?? 50;
    $connections = $query->paginate($perPage);

    // Statistiques rapides
    $stats = [
        'total_connections_today' => DB::table('user_connections')
            ->whereDate('login_at', today())
            ->count(),
        
        'unique_users_today' => DB::table('user_connections')
            ->whereDate('login_at', today())
            ->distinct('user_id')
            ->count('user_id'),
        
        'avg_session_duration' => DB::table('user_connections')
            ->whereNotNull('session_duration')
            ->whereDate('login_at', today())
            ->avg('session_duration'),
            
        'total_connections_this_week' => DB::table('user_connections')
            ->whereBetween('login_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count()
    ];

    return response()->json([
        'success' => true,
        'data' => $connections->items(),
        'pagination' => [
            'current_page' => $connections->currentPage(),
            'last_page' => $connections->lastPage(),
            'per_page' => $connections->perPage(),
            'total' => $connections->total(),
            'from' => $connections->firstItem(),
            'to' => $connections->lastItem()
        ],
        'stats' => $stats
    ]);
}

/**
 * Exporter l'historique en CSV
 */
public function exportConnectionHistory(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate();
    
    // if ($user->departement !== 'Administration') {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Accès non autorisé'
    //     ], 403);
    // }

    $request->validate([
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date|after_or_equal:date_from',
        'departement' => 'nullable|string'
    ]);

    $query = DB::table('user_connections')
                ->join('users', 'user_connections.user_id', '=', 'users.id')
                ->select([
                    'users.nom',
                    'users.prenom',
                    'users.email_pro',
                    // 'users.departement',
                    // 'users.role_id',
                    'user_connections.login_at',
                    'user_connections.logout_at',
                    'user_connections.session_duration',
                    'user_connections.ip_address'
                ])
                ->orderBy('user_connections.login_at', 'desc');

    // Appliquer les mêmes filtres
    if ($request->date_from) {
        $query->whereDate('user_connections.login_at', '>=', $request->date_from);
    }

    if ($request->date_to) {
        $query->whereDate('user_connections.login_at', '<=', $request->date_to);
    }

    if ($request->departement) {
        $query->where('users.departement', $request->departement);
    }

    $connections = $query->get();

    $filename = 'historique_connexions_' . date('Y-m-d_H-i-s') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"'
    ];

    $callback = function() use ($connections) {
        $file = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($file, [
            'Nom',
            'Prénom', 
            'Email professionnel',
            'Département',
            'Poste',
            'Date de connexion',
            'Heure de connexion',
            'Date de déconnexion',
            'Heure de déconnexion',
            'Durée de session (min)',
            'Adresse IP'
        ]);

        foreach ($connections as $connection) {
            $loginDate = $connection->login_at ? date('d/m/Y', strtotime($connection->login_at)) : '';
            $loginTime = $connection->login_at ? date('H:i:s', strtotime($connection->login_at)) : '';
            $logoutDate = $connection->logout_at ? date('d/m/Y', strtotime($connection->logout_at)) : '';
            $logoutTime = $connection->logout_at ? date('H:i:s', strtotime($connection->logout_at)) : '';
            $duration = $connection->session_duration ? round($connection->session_duration / 60, 2) : '';

            fputcsv($file, [
                $connection->nom,
                $connection->prenom,
                $connection->email_pro,
                // $connection->departement,
                // $connection->poste,
                $loginDate,
                $loginTime,
                $logoutDate,
                $logoutTime,
                $duration,
                $connection->ip_address
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

}