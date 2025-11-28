<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        // Vérifier si l'utilisateur a la permission
        $hasPermission = $user->permissions()
            ->where('name', $permission)
            ->orWhere('slug', $permission)
            ->exists();
        
        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission refusée',
                'required_permission' => $permission
            ], 403);
        }
        
        return $next($request);
    }
}