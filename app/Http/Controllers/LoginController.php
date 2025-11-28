<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Support\Facades\Hash;
use App\Models\Reserve;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {
            Log::info("arriver");
            // Validation des données d'entrée
            $validation = Validator::make($request->all(), [
                'email_pro' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                 
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
     

            // Récupération du département
            $departement = null;
            if ($user->role && $user->role->departement_id) {
                $departement = Departement::find($user->role->departement_id);
            }
            Log::info("copte ".$user->compte );
            if (!$user->compte)
            {
                return response()->json([
                    'success' => false,
                    'user' => [
                    'id' => $user->id,
                    'compte' => $user->compte,
                    
                    ],
            ], );
            }
            $connectionId = $this->logUserConnection($user, $request);


    

        

        $preferences=  $user->preferences()->value('auto_logout');
        Log::info("prefference users ". $preferences);
         if (!$preferences) {
            // Créer des préférences par défaut si elles n'existent pas
            $preferences = UserPreference::create([
                'user_id' => $user->id,
            ]);
        }   


        // Ajouter l'ID de connexion dans le token JWT
        $customClaims = ['connection_id' => $connectionId];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        Log::info("durre ". $preferences);
        // Génération du token JWT config('jwt.ttl', 60);
        $expirationMinutes = config('jwt.ttl', $preferences);
        $expiresAt = Carbon::now()->addMinutes($expirationMinutes);
        // Stocker l'ID de connexion dans la session pour pouvoir l'utiliser lors du logout
        session(['connection_id' => $connectionId]);
        $permisions=$user->permissions()
        ->select('permissions.id', 'permissions.nom', 'permissions.slug', 'permissions.description')
        ->get();
 Log::info("durre ". $expirationMinutes);

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
                'expires_in' => $expirationMinutes,
                'permisions' => $permisions
            ], 200);
            
        } catch (\Exception $e) {
             Log::error('Erreur lors de la connexion:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'data' => $request->all() // Log des données reçues pour debug
        ]);
        
            return response()->json([
                'success' => false,
                'message' => "Erreure lors de la connexion"
            ], 500);
        }
    }


    // Méthode pour enregistrer la connexion
private function logUserConnection($user, $request)
{
    return DB::table('user_connections')->insertGetId([
        'user_id' => $user->id,
        'login_at' => now(),
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'created_at' => now(),
        'updated_at' => now()
    ]);
}


// Méthode de déconnexion modifiée
public function logout(Request $request)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
        $payload = JWTAuth::parseToken()->getPayload();
        
        // Récupérer l'ID depuis le token
        $connectionId = $payload->get('connection_id');
        Log::info("id connect ". $connectionId);
        if ($connectionId) {
            $this->updateConnectionLogout($connectionId);
        } else {
            // Fallback : chercher la dernière connexion active
            $this->updateLastActiveConnection($user->id);
        }
        
        JWTAuth::invalidate(JWTAuth::getToken());
        
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        Log::error('Logout error:', ['error' => $e->getMessage()]);
        return response()->json(['success' => false], 500);
    }
}

private function updateConnectionLogout($connectionId)
{
    $connection = DB::table('user_connections')->find($connectionId);
    
    if ($connection && !$connection->logout_at) {
        $duration = now()->diffInSeconds($connection->login_at);
        
        DB::table('user_connections')
            ->where('id', $connectionId)
            ->update([
                'logout_at' => now(),
                'session_duration' => $duration,
                'updated_at' => now()
            ]);
        
        Log::info('Connection closed:', ['id' => $connectionId, 'duration' => $duration]);
    }
}

private function updateLastActiveConnection($userId)
{
    $lastConnection = DB::table('user_connections')
        ->where('user_id', $userId)
        ->whereNull('logout_at')
        ->orderBy('login_at', 'desc')
        ->first();
        
    if ($lastConnection) {
        $this->updateConnectionLogout($lastConnection->id);
        Log::info('Fallback connection closed:', ['id' => $lastConnection->id]);
    }
}
}