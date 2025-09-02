<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Departement;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;



class AuthController extends Controller
{
    public function inscription(Request $request)
    {   
        try {
        $data = $request->all();
        Log::info("User data received: " . json_encode($data));
        
        // Validation supplémentaire côté serveur
        if (empty(trim($data['nom'])) || empty(trim($data['prenom']))) {
            return response()->json([
                'success' => false,
                'message' => 'Le nom et prénom sont obligatoires',
            ], 422);
        }
        

        // Vérification des doublons AVANT tout traitement
        $duplicateChecks = [
            'email' => User::where('email', trim($data['email']))->first(),
            'telephone' => User::where('telephone', trim($data['telephone']))->first(),
        ];
        
        // Vérification par combinaison nom + prénom + date de naissance
        $personExists = User::where('nom', trim($data['nom']))
                           ->where('prenom', trim($data['prenom']))
                           ->where('date_naissance', $data['dateNaissance'])
                           ->first();
        
        if ($duplicateChecks['email']) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte avec cette adresse email existe déjà.',
                'field' => 'email'
            ], 422);
        }
        
        if ($duplicateChecks['telephone']) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte avec ce numéro de téléphone existe déjà.',
                'field' => 'telephone'
            ], 422);
        }
        
        if ($personExists) {
            return response()->json([
                'success' => false,
                'message' => 'Une personne avec ces informations (nom, prénom, date de naissance) existe déjà.',
                'field' => 'person_identity'
            ], 422);
        }
        
        // Récupérer le poste 
        $nomPoste = trim($data['poste']);
        Log::info('Recherche du poste:', ['nom_poste' => $nomPoste]);
        
        $poste = Role::where('nom', $nomPoste)->first();

        if (!$poste) {
            Log::error('Poste non trouvé:', ['poste_recherche' => $nomPoste]);
            
            $postesDisponibles = Role::pluck('nom')->toArray();
            Log::info('Postes disponibles:', ['postes' => $postesDisponibles]);
            
            return response()->json([
                'success' => false,
                'message' => 'Le poste spécifié n\'existe pas',
                'poste_recherche' => $nomPoste,
                'postes_disponibles' => $postesDisponibles
            ], 422);
        }

        Log::info('Poste trouvé:', ['poste_id' => $poste->id, 'poste_nom' => $poste->nom]);
        
        // Préparer les données avec trim pour éviter les espaces
        $userData = [
            'nom' => trim($data['nom']),
            'prenom' => trim($data['prenom']),
            'email' => trim($data['email']),
            'telephone' => trim($data['telephone']),
            'date_naissance' => $data['date_naissance'], // Utiliser le bon nom de champ
            'lieu_naissance' => trim($data['lieu_naissance']), // Utiliser le bon nom de champ
            'role_id' => $poste->id,
            'statut' => false // Explicitly set status
        ];

        Log::info('Data to insert final:', [
                'userData' => $userData,
                'email check' => [
                  'value' => $userData['email'],
                  'isnull' => is_null($userData['email']),
                  'empty' => empty($userData['email']),
                ],

        ]);

        // Créer l'utilisateur
        $user = User::create($userData);

        if (!$user) {
            Log::error('Échec de création de l\'utilisateur');
            throw new \Exception("Impossible de créer l'utilisateur");
        }

        Log::info('Utilisateur créé avec succès:', [
            'user_id' => $user->id,
            'role_id' => $user->role_id
        ]);

        // Récupérer le département
        $departement = null;
        $user->load('role');
        
        if ($user->role && $user->role->departement_id) {
            $departement = Departement::find($user->role->departement_id);
            Log::info('Département trouvé:', ['departement' => $departement->nom ?? 'null']);
        }

        $response = [
            'success' => true,
            'message' => 'Inscription réussie. Votre compte est en attente d\'approbation.',
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'statut' => $user->statut,
                'role' => $user->role ? $user->role->nom : null,
                'role_id' => $user->role_id,
                'departement' => $departement ? $departement->nom : null,
            ]
        ];

        return response()->json($response, 201);

    } catch (\Exception $e) {
        Log::error('Erreur lors de l\'inscription:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'data' => $request->all() // Log des données reçues pour debug
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de l\'inscription.',
            'debug_info' => config('app.debug') ? [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ] : null
        ], 500);
    }}
       
    public function loadposte()
    {   
        try {
            $postes = Role::all();
            Log::info('Postes chargés:', ['count' => $postes->count()]);
           
            return response()->json([
                'success' => true,
                'postes' => $postes,
            ]);
            Log::info('poste returner'.$postes);
        }
        catch(\Exception $e)
        {
            Log::error('Erreur chargement postes:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => "Une erreur du serveur c'est produit",
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function approuver(Request $request, $userId)
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $statut = $request->input('statut');
            $password = $request->input('password');
            $user->statut = $statut;
            $user->password = Hash::make($password);
            $user->save();
            
            $departement = null;
            if ($user->poste && $user->poste->departement_id) {
                $departement = Departement::find($user->poste->departement_id);
            }
         
            return response()->json([
                'success' => true,
                'message' => "utilisateur approuver avec succès",
                'user' => [
                    'nom' => $user->nom,
                    'email_pro' => $user->email_pro,
                    'telephone' => $user->telephone,
                    'statut' => $user->statut,
                    'poste' => $user->poste ? $user->poste->nom : null,
                    'departement' => $departement ? $departement->nom : null,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur approbation:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation',
            ], 500);
        }

    }

    public function resetpassword(Request $request, $userId)
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $password = $request->input('newPassword');
            $user->password = $password;
            $user->save();
            
            $departement = null;
            if ($user->poste && $user->poste->departement_id) {
                $departement = Departement::find($user->poste->departement_id);
            }
         
            return response()->json([
                'success' => true,
                'message' => "Mot de passe modifier avec succès !!",
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                    'statut' => $user->statut,
                    'poste' => $user->poste ? $user->poste->nom : null,
                    'poste_id' => $user->poste_id,
                    'departement' => $departement ? $departement->nom : null,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur approbation:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du mot de passe',
            ], 500);
        }
    }
}





