<?php

namespace App\Http\Controllers;
use App\Models\Departement;
use App\Models\Poste;
use App\Http\Requests\StoreDepartementRequest;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DepartementController extends Controller
{
    /**
     * Liste tous les départements avec leurs postes
     */
    public function index(): JsonResponse
    {
        try {
            $departements = Departement::with(['postes', 'roles'])
                ->withCount(['postes', 'roles'])
                ->orderBy('nom')
                ->get();

            return response()->json([
                'success' => true,
                'departements' => $departements
            ]);
        } catch (Exception $e) {
            Log::error('Erreur liste départements: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des départements'
            ], 500);
        }
    }

    /**
     * Affiche un département spécifique
     */
    public function show($id): JsonResponse
    {
        try {
            $departement = Departement::with(['postes', 'roles.users'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'departement' => $departement
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Département non trouvé'
            ], 404);
        }
    }

    /**
     * Crée un nouveau département avec ses postes
     */
    public function store(Request $request): JsonResponse
{
    // Validation
    $validator = Validator::make($request->all(), [
        'nom' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'postes' => 'required|array|min:1',
        'postes.*' => 'required|string|max:255|distinct'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    DB::beginTransaction();

    try {
        // ✅ Vérifier si le département existe déjà (insensible à la casse)
        $existingDepartement = Departement::whereRaw('LOWER(nom) = ?', [strtolower($request->nom)])->first();
        
        if ($existingDepartement) {
            return response()->json([
                'success' => false,
                'message' => "Le département '{$request->nom}' existe déjà"
            ], 422);
        }

        // Créer le département
        $departement = Departement::create([
            'nom' => $request->nom,
            'description' => $request->description ?? null,
            'is_active' => true
        ]);

        // Vérifier que l'ID a bien été créé
        if (!$departement->id) {
            throw new Exception("Le département n'a pas été créé correctement");
        }

        Log::info("Département créé avec ID: " . $departement->id);

        $postesCreated = [];
        foreach ($request->postes as $posteNom) {
            
            $existingPoste = Role::where('nom', $posteNom)
                ->where('departement_id', $departement->id)
                ->first();
            
            if ($existingPoste) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Le poste '{$posteNom}' existe déjà dans ce département"
                ], 422);
            }

            $poste = Role::create([
                'nom' => $posteNom,
                'departement_id' => $departement->id,
                'is_active' => true
            ]);

            $postesCreated[] = $poste;
            Log::info("Poste créé: {$poste->nom}");
        }

        // Recharger le département avec ses roles/postes
        $departement->load('postes');

        DB::commit();

        Log::info("Département '{$departement->nom}' créé avec " . count($postesCreated) . " postes", [
            'departement_id' => $departement->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Département '{$departement->nom}' créé avec succès avec " . count($postesCreated) . " poste(s)",
            'departement' => $departement
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Erreur création département: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création du département',
            'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
        ], 500);
    }
}
    /**
     * Met à jour un département
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255|unique:departements,nom,' . $id,
            
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $departement = Departement::findOrFail($id);
            $departement->update($request->only(['nom', 'description', 'is_active']));

            DB::commit();

            Log::info("Département mis à jour: {$departement->nom}", [
                'departement_id' => $departement->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Département mis à jour avec succès',
                'departement' => $departement->load(['postes', 'roles'])
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour département: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Supprime un département
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $departement = Departement::with(['postes', 'roles.users'])->findOrFail($id);

            // Vérifier si des rôles (et donc des users) sont liés à ce département
            $nbUsers = $departement->users()->count();
            if ($nbUsers > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de supprimer ce département car {$nbUsers} utilisateur(s) y sont rattachés via des rôles"
                ], 422);
            }

            $nomDepartement = $departement->nom;
            $nbPostes = $departement->postes()->count();
            $nbRoles = $departement->roles()->count();

            // Suppression (les postes et rôles seront supprimés en cascade)
            $departement->delete();

            DB::commit();

            Log::warning("Département supprimé: {$nomDepartement}", [
                'departement_id' => $id,
                'postes_supprimes' => $nbPostes,
                'roles_supprimes' => $nbRoles,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Département '{$nomDepartement}' supprimé avec ses {$nbPostes} poste(s) et {$nbRoles} rôle(s)"
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression département: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }
}