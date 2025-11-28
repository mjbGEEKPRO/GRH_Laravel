<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class TrackConnectionStatus
{
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            // Si le token est valide, continuer
            return $next($request);
            
        } catch (\Exception $e) {
            // Si le token est invalide/expiré, marquer la déconnexion automatique
            $connectionId = session('connection_id');
            
            if ($connectionId) {
                $connection = DB::table('user_connections')->find($connectionId);
                if ($connection && !$connection->logout_at) {
                    $sessionDuration = now()->diffInSeconds($connection->login_at);
                    
                    DB::table('user_connections')
                        ->where('id', $connectionId)
                        ->update([
                            'logout_at' => now(),
                            'session_duration' => $sessionDuration,
                            'updated_at' => now()
                        ]);
                }
                
                session()->forget('connection_id');
            }
            
            // Retourner l'erreur d'authentification
            return response()->json([
                'success' => false,
                'message' => 'Token expiré ou invalide'
            ], 401);
        }
    }
}