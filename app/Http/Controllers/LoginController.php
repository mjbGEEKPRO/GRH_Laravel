<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Support\Facades\Hash;
use App\Models\Reserve;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        Log::info("debut validate",$request->all());
        try {
            // Validation des données d'entrée
            $validation = Validator::make($request->all(), [
                'email_pro' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'regex:/^[a-z][a-z0-9._-]*@gmail.com$/'
                ],
                'password' => [
                    'required',
                    'min:8',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/\d/',
                    'regex:/[@$!%*#?&]/'
                ]
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez vérifier vos informations et réessayer',
                    'errors' => $validation->errors()
                ], 422);
            }

            // Vérifier si l'utilisateur existe
            $user = User::where('email_pro', $request->email_pro)->first();
        

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => "Aucun compte n'a les informations que vous avez renseigner"
                ], 404);
            }

            //  Vérifier le statut de l'utilisateur
            if (!$user->statut) {
                return response()->json([
                    'success' => false,
                    'message' => "Votre demande n'a pas encore été approuvée"
                ], 403);
            }

            //  Vérifier le mot de passe
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => "Votre adresse email professionnel ou votre mot de passe est incorrect"
                ], 401);
            }

            // Après je peuc charger la relation poste maintenant qu'on sait que l'utilisateur est valide
            $user = User::with('role')->where('email_pro', $request->email_pro)->first();
              Log::info('User avec relation:', [
            'user_id' => $user->id,
            'role_id' => $user->role_id,
            'role_object' => $user->role
        ]);
            // Récupération du département
            $departement = null;
            if ($user->role && $user->role->departement_id) {
                $departement = Departement::find($user->role->departement_id);
            }

            // Génération du token JWT config('jwt.ttl', 60);
            $token = JWTAuth::fromUser($user);
            $expirationMinutes = config('jwt.ttl', 2);
            $expiresAt = Carbon::now()->addMinutes($expirationMinutes);

            return response()->json([
                'success' => true,
                'message' => "Veuillez patienter, nous configurons votre compte",
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email_pro' => $user->email_pro,
                    'telephone' => $user->telephone,
                    'statut' => $user->statut,
                    'poste' => $user->role ? $user->role->nom : null,
                    'poste_id' => $user->role_id,
                    'departement' => $departement ? $departement->nom : null,
                ],
                'access_token' => $token,
                'expires_at' => $expiresAt->toISOString(),
                'expires_in' => $expirationMinutes
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur de connexion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erreure lors de la connexion"
            ], 500);
        }
    }
}