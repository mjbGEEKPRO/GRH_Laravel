<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Vérifie que l'utilisateur a le rôle administrateur
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        
        
         $user = $request->user();
        
        // Vérifier que l'utilisateur a un rôle
        if (!$user->role_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé : Aucun rôle attribué'
            ], 403);
        }
        
        // Récupérer le rôle de l'utilisateur
        $role = DB::table('roles')->where('id', $user->role_id)->first();
        
        // Vérifier si c'est un administrateur
        if (!$role || strtolower($role->nom) !== 'administrateur') {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé : Privilèges administrateur requis'
            ], 403);
        }
        
        return $next($request);
    }
}