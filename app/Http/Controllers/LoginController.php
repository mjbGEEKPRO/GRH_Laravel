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
    // Configuration du système de blocage
    const MAX_LOGIN_ATTEMPTS = 4;          // Nombre maximum de tentatives
    const LOCKOUT_TIME_HOURS = 3;          // Durée du blocage en heures
    const ATTEMPT_WINDOW_MINUTES = 15;     // Fenêtre de temps pour compter les tentatives

    public function login(Request $request)
    {
        try {
            Log::info("Tentative de connexion");
            
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

            $email = $request->email_pro;
            $ipAddress = $request->ip();

            // 1. VÉRIFIER SI L'IP EST BLOQUÉE
            // $ipBlockInfo = $this->checkIpBlocked($ipAddress);
            // if ($ipBlockInfo['blocked']) {
            //     $this->logFailedAttempt($email, $ipAddress, 'ip_blocked', $request);
                
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Trop de tentatives échouées. Votre accès est temporairement bloqué.',
            //         'blocked_until' => $ipBlockInfo['blocked_until'],
            //         'remaining_time' => $ipBlockInfo['remaining_time'],
            //         'attempts' => $ipBlockInfo['attempts']
            //     ], 429);
            // }

            // 2. VÉRIFIER SI L'UTILISATEUR EXISTE
            $user = User::where('email_pro', $email)->first();

            if (!$user) {
                $this->logFailedAttempt($email, $ipAddress, 'account_not_found', $request);
                
                return response()->json([
                    'success' => false,
                    'message' => "Aucun compte n'a les informations que vous avez renseignées"
                ], 404);
            }

            // 3. VÉRIFIER SI LE COMPTE EST BLOQUÉ
            $accountBlockInfo = $this->checkAccountBlocked($user->id, $email);
            if ($accountBlockInfo['blocked']) {
                $this->logFailedAttempt($email, $ipAddress, 'account_locked', $request);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est temporairement bloqué suite à plusieurs tentatives échouées.',
                    'blocked_until' => $accountBlockInfo['blocked_until'],
                    'remaining_time' => $accountBlockInfo['remaining_time'],
                    'attempts' => $accountBlockInfo['attempts']
                ], 423);
            }

            // 4. VÉRIFIER LE STATUT DE L'UTILISATEUR
            if (!$user->statut) {
                $this->logFailedAttempt($email, $ipAddress, 'account_not_approved', $request);
                
                return response()->json([
                    'success' => false,
                    'message' => "Votre demande n'a pas encore été approuvée"
                ], 403);
            }

            // 5. VÉRIFIER LE MOT DE PASSE
            if (!Hash::check($request->password, $user->password)) {
                // Enregistrer la tentative échouée
                $this->logFailedAttempt($email, $ipAddress, 'wrong_password', $request);
                
                // Compter les tentatives récentes
                $recentAttempts = $this->getRecentFailedAttempts($email, $ipAddress);
                $remainingAttempts = self::MAX_LOGIN_ATTEMPTS - $recentAttempts;
                
                if ($remainingAttempts <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Nombre maximum de tentatives atteint. Votre compte est temporairement bloqué pour " . self::LOCKOUT_TIME_HOURS . " heures.",
                        'blocked' => true,
                        'attempts' => $recentAttempts
                    ], 429);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => "Votre adresse email professionnelle ou votre mot de passe est incorrect",
                    'remaining_attempts' => $remainingAttempts,
                    'warning' => $remainingAttempts <= 2 ? "Attention : il vous reste {$remainingAttempts} tentative(s)" : null
                ], 401);
            }

            // ✅ CONNEXION RÉUSSIE - Nettoyer les tentatives échouées
            $this->clearFailedAttempts($email, $ipAddress);

            // Charger la relation role
            $user = User::with('role')->where('email_pro', $email)->first();

            // Récupération du département
            $departement = null;
            if ($user->role && $user->role->departement_id) {
                $departement = Departement::find($user->role->departement_id);
            }

            Log::info("Compte: " . $user->compte);
            
            if (!$user->compte) {
                return response()->json([
                    'success' => false,
                    'user' => [
                        'id' => $user->id,
                        'compte' => $user->compte,
                    ],
                ]);
            }

            // Enregistrer la connexion réussie
            $connectionId = $this->logUserConnection($user, $request);

            // Récupérer les préférences
            $preferences = $user->preferences()->value('auto_logout');
            Log::info("Préférences utilisateur: " . $preferences);
            
            if (!$preferences) {
                $preferences = UserPreference::create([
                    'user_id' => $user->id,
                ]);
            }

            // Générer le token JWT
            $customClaims = ['connection_id' => $connectionId];
            $token = JWTAuth::customClaims($customClaims)->fromUser($user);
            
            $expirationMinutes = config('jwt.ttl', $preferences);
            $expiresAt = Carbon::now()->addMinutes($expirationMinutes);
            
            session(['connection_id' => $connectionId]);
            
            $permisions = $user->permissions()
                ->select('permissions.id', 'permissions.nom', 'permissions.slug', 'permissions.description')
                ->get();

            Log::info("Durée: " . $expirationMinutes);

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
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de la connexion"
            ], 500);
        }
    }

    /**
     * Vérifie si une IP est bloquée
     */
    private function checkIpBlocked($ipAddress)
    {
        $lockoutTime = Carbon::now()->subHours(self::LOCKOUT_TIME_HOURS);
        
        $attempts = DB::table('failed_login_attempts')
            ->where('ip_address', $ipAddress)
            ->where('attempted_at', '>=', Carbon::now()->subMinutes(self::ATTEMPT_WINDOW_MINUTES))
            ->count();

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $firstAttempt = DB::table('failed_login_attempts')
                ->where('ip_address', $ipAddress)
                ->where('attempted_at', '>=', Carbon::now()->subMinutes(self::ATTEMPT_WINDOW_MINUTES))
                ->orderBy('attempted_at', 'asc')
                ->first();

            $blockedUntil = Carbon::parse($firstAttempt->attempted_at)->addHours(self::LOCKOUT_TIME_HOURS);
            $now = Carbon::now();

            if ($now->lessThan($blockedUntil)) {
                $remainingMinutes = $now->diffInMinutes($blockedUntil);
                
                return [
                    'blocked' => true,
                    'blocked_until' => $blockedUntil->format('d/m/Y H:i:s'),
                    'remaining_time' => $this->formatRemainingTime($remainingMinutes),
                    'attempts' => $attempts
                ];
            }
        }

        return ['blocked' => false];
    }

    /**
     * Vérifie si un compte est bloqué
     */
    private function checkAccountBlocked($userId, $email)
    {
        $lockoutTime = Carbon::now()->subHours(self::LOCKOUT_TIME_HOURS);
        
        $attempts = DB::table('failed_login_attempts')
            ->where('email', $email)
            ->where('attempted_at', '>=', Carbon::now()->subMinutes(self::ATTEMPT_WINDOW_MINUTES))
            ->count();

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $firstAttempt = DB::table('failed_login_attempts')
                ->where('email', $email)
                ->where('attempted_at', '>=', Carbon::now()->subMinutes(self::ATTEMPT_WINDOW_MINUTES))
                ->orderBy('attempted_at', 'asc')
                ->first();

            $blockedUntil = Carbon::parse($firstAttempt->attempted_at)->addHours(self::LOCKOUT_TIME_HOURS);
            $now = Carbon::now();

            if ($now->lessThan($blockedUntil)) {
                $remainingMinutes = $now->diffInMinutes($blockedUntil);
                
                return [
                    'blocked' => true,
                    'blocked_until' => $blockedUntil->format('d/m/Y H:i:s'),
                    'remaining_time' => $this->formatRemainingTime($remainingMinutes),
                    'attempts' => $attempts
                ];
            }
        }

        return ['blocked' => false];
    }

    /**
     * Enregistre une tentative de connexion échouée
     */
    private function logFailedAttempt($email, $ipAddress, $reason, $request)
    {
        DB::table('failed_login_attempts')->insert([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent(),
            'reason' => $reason,
            'attempted_at' => now()
        ]);

        Log::warning("Tentative de connexion échouée", [
            'email' => $email,
            'ip' => $ipAddress,
            'reason' => $reason
        ]);
    }

    
      //Compte les tentatives échouées récentes
     
    private function getRecentFailedAttempts($email, $ipAddress)
    {
        return DB::table('failed_login_attempts')
            ->where(function($query) use ($email, $ipAddress) {
                $query->where('email', $email)
                      ->orWhere('ip_address', $ipAddress);
            })
            ->where('attempted_at', '>=', Carbon::now()->subMinutes(self::ATTEMPT_WINDOW_MINUTES))
            ->count();
    }

    /**
     * Nettoie les tentatives échouées après une connexion réussie
     */
    private function clearFailedAttempts($email, $ipAddress)
    {
        DB::table('failed_login_attempts')
            ->where(function($query) use ($email, $ipAddress) {
                $query->where('email', $email)
                      ->orWhere('ip_address', $ipAddress);
            })
            ->delete();

        Log::info("Tentatives échouées nettoyées pour: " . $email);
    }

    /**
     * Formate le temps restant de blocage
     */
    private function formatRemainingTime($minutes)
    {
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'min' : '');
        }
        return $minutes . ' minutes';
    }

    /**
     * Enregistre la connexion de l'utilisateur
     */
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

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $payload = JWTAuth::parseToken()->getPayload();
            
            $connectionId = $payload->get('connection_id');
            Log::info("ID connexion: " . $connectionId);
            
            if ($connectionId) {
                $this->updateConnectionLogout($connectionId);
            } else {
                $this->updateLastActiveConnection($user->id);
            }
            
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Erreur de déconnexion:', ['error' => $e->getMessage()]);
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
            
            Log::info('Connexion fermée:', ['id' => $connectionId, 'durée' => $duration]);
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
            Log::info('Connexion de secours fermée:', ['id' => $lastConnection->id]);
        }
    }
}