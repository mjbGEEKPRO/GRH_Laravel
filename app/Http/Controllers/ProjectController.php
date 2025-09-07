<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectTeam;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $query = Project::with(['creator', 'tasks', 'teams']);
            
            // Si pas admin, filtrer par département
            if ($user->departement !== 'Administration') {
                $query->where(function($q) use ($user) {
                    $q->whereJsonContains('departements', $user->departement)
                      ->orWhere('created_by', $user->id);
                });
            }
            
            $departement = Departement::whereNotIn("nom", ["Administration"])->get();
            $projects = $query->latest()->get();

            return response()->json([
                'success' => true,
                'projects' => $projects,
                'departements' => $departement
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des projets:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des projets.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255|unique:projects',
                'description' => 'nullable|string',
                'budget' => 'required|numeric|min:0',
                'date_fin_prevue' => 'required|date|after:today',
                'departements' => 'required|array|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = JWTAuth::parseToken()->authenticate();
            
            // Vérifier permission de création
            if (!$this->canCreateProject($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée'
                ], 403);
            }

            $project = Project::create([
                ...$request->only(['nom', 'description', 'budget', 'date_fin_prevue', 'departements']),
                'created_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Projet créé avec succès !',
                'data' => $project->load(['creator', 'tasks', 'teams'])
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du projet:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $request->all() 
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du projet.'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $project = Project::with(['creator', 'tasks.assignedUser', 'teams.members'])->findOrFail($id);
            
            // Vérifier accès
            if (!$this->canAccessProject(JWTAuth::parseToken()->authenticate(), $project)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $project
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du projet:', [
                'message' => $e->getMessage(),
                'project_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé'
            ], 404);
        }
    }

    public function createTeams(Request $request, $projectId)
    {
        try {
            // Debug : afficher les données reçues
            Log::info('Données reçues pour createTeams:', [
                'request_data' => $request->all(),
                'project_id' => $projectId,
                'teams_data' => $request->get('teams'),
                'teams_count' => is_array($request->get('teams')) ? count($request->get('teams')) : 'not array'
            ]);

            // Validation des données
            $validator = Validator::make($request->all(), [
                'teams' => 'required|array|min:1',
                'teams.*.nom' => 'required|string|max:255',
                'teams.*.departement' => 'required|string|max:100',
                'teams.*.description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed for createTeams:', [
                    'errors' => $validator->errors()->toArray(),
                    'data' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier que le projet existe
            $project = Project::find($projectId);
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé'
                ], 404);
            }
            
            // Vérifier les permissions
            $user = JWTAuth::parseToken()->authenticate();
            if (!$this->canManageProject($user, $project)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée pour créer des équipes sur ce projet'
                ], 403);
            }

            // Créer les équipes
            $createdTeams = [];
            foreach ($request->teams as $teamData) {
                // Vérifier si une équipe avec le même nom existe déjà pour ce projet
                $existingTeam = ProjectTeam::where('project_id', $projectId)
                                         ->where('nom', $teamData['nom'])
                                         ->first();
                
                if ($existingTeam) {
                    return response()->json([
                        'success' => false,
                        'message' => "Une équipe nommée '{$teamData['nom']}' existe déjà pour ce projet"
                    ], 409);
                }

                $team = ProjectTeam::create([
                    'project_id' => $projectId,
                    'nom' => $teamData['nom'],
                    'departement' => $teamData['departement'],
                    'description' => $teamData['description'] ?? null,
                ]);
                
                $createdTeams[] = $team;
            }

            return response()->json([
                'success' => true,
                'message' => count($createdTeams) . ' équipe(s) créée(s) avec succès !',
                'data' => $createdTeams
            ], 201);
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Erreur de base de données lors de la création des équipes:', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'project_id' => $projectId,
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de base de données lors de la création des équipes'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création des équipes:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'project_id' => $projectId,
                'data' => $request->all() 
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création des équipes'
            ], 500);
        }
    }

    // Méthodes d'autorisation 
    private function canCreateProject($user)
    {
        return $user->departement === 'Administration' || 
               str_contains($user->role, 'Administrateur') || 
               str_contains($user->role, 'Manager');
    }

    private function canAccessProject($user, $project)
    {
        return $user->departement === 'Administration' || 
               in_array($user->departement, $project->departements) ||
               $project->created_by === $user->id;
    }

    private function canManageProject($user, $project)
    {
        return $user->departement === 'Administration' || 
               $project->created_by === $user->id ||
               str_contains($user->role, 'Administrateur') || 
               str_contains($user->role, 'Manager');
    }

    public function destroy($id)
    {
        try {
            $project = Project::find($id);
            Log::info("id projet ". $id);
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projet non trouvé'
                ], 404);
            }

            $user = JWTAuth::parseToken()->authenticate();

            // Vérifier les permissions (seul admin ou créateur peut supprimer)
            if (!$this->canManageProject($user, $project)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les permissions pour supprimer ce projet'
                ], 403);
            }

            // Vérifier s'il y a des tâches liées
            $tasksCount = $project->tasks()->count();
            if ($tasksCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de supprimer le projet. Il contient {$tasksCount} tâche(s)."
                ], 400);
            }

            // Supprimer les équipes associées d'abord
            $project->teams()->delete();
            
            // Puis supprimer le projet
            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Projet supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du projet:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'project_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression du projet'
            ], 500);
        }
    }
}
