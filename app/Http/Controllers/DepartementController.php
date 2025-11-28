<?php

namespace App\Http\Controllers;
use App\Models\Departement;
use App\Models\Poste;
use App\Http\Requests\StoreDepartementRequest;
use App\Models\Role;
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
            $departements = Departement::with('postes')
                ->withCount('postes')
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
            $departement = Departement::with('postes')->findOrFail($id);

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
    public function store(StoreDepartementRequest $request): JsonResponse
    {
        Log::info("arriver");
        DB::beginTransaction();

        try {
            // Créer le département
            $departement = Departement::create([
                'nom' => $request->nom,
                'description' => $request->description,
                'is_active' => true
            ]);

            // Créer les postes associés
            $postesData = [];
            foreach ($request->postes as $posteNom) {
                // Vérifier si le poste existe déjà dans un autre département
                $existingPoste = Role::where('nom', $posteNom)
                    ->whereHas('departement', function($query) use ($departement) {
                        $query->where('id', '!=', $departement->id);
                    })
                    ->first();

                if ($existingPoste) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Le poste '{$posteNom}' existe déjà dans le département '{$existingPoste->departement->nom}'"
                    ], 422);
                }

                $postesData[] = [
                    'nom' => $posteNom,
                    'departement_id' => $departement->id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // Insertion en masse pour optimisation
            Role::insert($postesData);

            // Recharger le département avec ses postes
            $departement->load('postes');

            DB::commit();

          

            return response()->json([
                'success' => true,
                'message' => "Département '{$departement->nom}' créé avec succès avec " . count($postesData) . " poste(s)",
                'departement' => $departement
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur création département: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du département',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Met à jour un département
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'nom' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Role::unique('departements', 'nom')->ignore($id)
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean'
        ]);

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
                'departement' => $departement->load('postes')
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
     * Supprime un département (et ses postes en cascade)
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $departement = Departement::with('postes', 'users')->findOrFail($id);

            // Vérifier si des utilisateurs sont liés à ce département
            if ($departement->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce département car ' . $departement->users()->count() . ' utilisateur(s) y sont rattachés'
                ], 422);
            }

            $nomDepartement = $departement->nom;
            $nbPostes = $departement->postes()->count();

            // Suppression (les postes seront supprimés en cascade)
            $departement->delete();

            DB::commit();

            Log::warning("Département supprimé: {$nomDepartement}", [
                'departement_id' => $id,
                'postes_supprimes' => $nbPostes,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Département '{$nomDepartement}' et ses {$nbPostes} poste(s) supprimés avec succès"
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

    /**
     * Vérifie si un poste existe déjà
     */
    public function checkPosteExists(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string',
            'departement_id' => 'nullable|exists:departements,id'
        ]);

        $query = Role::where('nom', $request->nom);

        if ($request->departement_id) {
            $query->where('departement_id', '!=', $request->departement_id);
        }

        $existingPoste = $query->with('departement')->first();

        if ($existingPoste) {
            return response()->json([
                'exists' => true,
                'poste' => $existingPoste,
                'message' => "Ce poste existe déjà dans le département '{$existingPoste->departement->nom}'"
            ]);
        }

        return response()->json([
            'exists' => false,
            'message' => 'Ce poste est disponible'
        ]);
    }
}