<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use App\Models\ProjectTeam;
use App\Models\Team;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserInfoControler extends Controller
{
    public function getInfo(){
        try {
            // Récupérer les utilisateurs avec leurs relations
            $users = User::where('statut', true)->with(['role','permissions','teams'])->get();
            $teams = ProjectTeam::with('project')->get();
            $permissions = Permission::all();
            $postes = Role::all();
            Log::info("user rcupérer ". $permissions);
            return response()->json([
                'users' => $users,
                'role' => $postes,
                'permissions' => $permissions,
                'teams' => $teams,
            ]);
        }
        catch (\Exception $e)
        {
            Log::info(" Erreur". $e);
            return response()->json([
                'message' => 'Erreur interne: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $userId)
{
    try {
        $user = User::findOrFail($userId);
        Log::info("role actuel" . $user->role_id);
        $user->role_id = $request->input('role_id');
        $user->save(); 
        Log::info("new role" . $user->role_id);

        
        $user->permissions()->sync($request->input('permissions', []));
        $user->teams()->sync($request->input('teams', []));

        
        $user->load('role', 'permissions', 'teams');
        Log::info("new user return" . $user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur update user: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ], 500);
    }
}

    public function EditUser(Request $request, $id)
    {
        try {
            $validationData = Validator::make($request->all(), [
                'nom' => 'nullable|string|max:255',
                'email_pro' => 'nullable|string|email|max:255',
            ]);
            
              if ($validationData->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez vérifier vos informations et réessayer',
                ], 422);
            }

            $user = User::findOrFail($id);
            $updateData = [];
            

            // Vérification de l'email uniquement si fourni
            if (!empty($request->email_pro)) {
                $existeUser = User::where('email_pro', $request->email_pro)
                                   ->where('id', '!=', $id)
                                   ->first();
                if ($existeUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet email professionnel est déjà utilisé par un autre employer',
                    ], 422);
                }
               $updateData['email_pro']= $request->email_pro;
            }
            
            if (!empty($request->nom)) {
                $updateData['nom'] = $request->nom;
            }

            // Mettre à jour seulement si des données sont présentes
            if (!empty($updateData)) {
                $user->update($updateData);
                Log::info("User info update dans le if". $user);

            }

            //Recharger l'utilisateur avec ses relations
            $user->load('teams', 'role', 'permissions');
            Log::info("User info update tableau  ". $updateData['email_pro']. $updateData['nom']);
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur modifié avec succès',
                'user' => $user
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur EditUser: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function UserModal(){
        try {
            // Récupérer les utilisateurs avec leurs relations
            $users = User::all();
            Log::info("user send ", $users);
            return response()->json([
                'users' => $users,
            ]);
        }
        catch (\Exception $e)
        {
            Log::info(" Erreur". $e);
            return response()->json([
                'message' => 'Erreur interne: ' . $e->getMessage(),
            ], 500);
        }
    }
}