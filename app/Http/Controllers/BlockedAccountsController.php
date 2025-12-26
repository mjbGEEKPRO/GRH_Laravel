<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BlockedAccountsController extends Controller
{
    /**
     * Récupère le nombre de comptes bloqués
     */
    public function getBlockedCount()
    {
        try {
            // Compter les utilisateurs bloqués (statut = 0)
            $blockedUsers = DB::table('users')
                ->where('statut', 0)
                ->count();
            
            // Compter les IP bloquées (non expirées)
            $blockedIPs = DB::table('ip_blocks')
                ->where('blocked_until', '>', Carbon::now())
                ->count();
            
            return response()->json([
                'success' => true,
                'count' => $blockedUsers + $blockedIPs,
                'blocked_users' => $blockedUsers,
                'blocked_ips' => $blockedIPs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du compteur'
            ], 500);
        }
    }

    /**
     * Récupère tous les comptes bloqués
     */
    public function getBlockedAccounts()
    {
        try {
            // Utilisateurs bloqués
            $blockedUsers = DB::table('users')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->leftJoin('departements', 'roles.departement_id', '=', 'departements.id')
                ->where('users.statut', 0)
                ->select(
                    'users.id',
                    'users.nom',
                    'users.prenom',
                    'users.email_pro as email',
                    'users.updated_at as blocked_at',
                    'roles.nom as role',
                    'departements.nom as department',
                    DB::raw("CONCAT(users.prenom, ' ', users.nom) as name"),
                    DB::raw("'Compte désactivé par l\\'administrateur' as reason"),
                    DB::raw("'user' as type")
                )
                ->get();

            // IP bloquées
            $blockedIPs = DB::table('ip_blocks')
                ->where('blocked_until', '>', Carbon::now())
                ->select(
                    'id',
                    'ip_address',
                    'blocked_until',
                    'attempts_count',
                    'created_at as first_attempt',
                    'updated_at as last_attempt',
                    DB::raw("'ip' as type")
                )
                ->get();

            // Pour chaque IP, récupérer les emails tentés
            foreach ($blockedIPs as $ip) {
                $emails = DB::table('failed_login_attempts')
                    ->where('ip_address', $ip->ip_address)
                    ->where('attempted_at', '>=', Carbon::now()->subHours(24))
                    ->distinct()
                    ->pluck('email')
                    ->toArray();
                
                $ip->emails_tried = $emails;
            }

            return response()->json([
                'success' => true,
                'users' => $blockedUsers,
                'ips' => $blockedIPs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des comptes bloqués',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les utilisateurs actifs
     */
    public function getActiveUsers()
    {
        try {
            $activeUsers = DB::table('users')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->leftJoin('departements', 'roles.departement_id', '=', 'departements.id')
                ->leftJoin('user_connections', function($join) {
                    $join->on('users.id', '=', 'user_connections.user_id')
                         ->whereRaw('user_connections.id = (
                             SELECT MAX(id) FROM user_connections 
                             WHERE user_id = users.id
                         )');
                })
                ->where('users.statut', 1)
                ->where('users.compte', 1)
                ->select(
                    'users.id',
                    'users.nom',
                    'users.prenom',
                    'users.email_pro as email',
                    'roles.nom as role',
                    'departements.nom as department',
                    'user_connections.login_at as last_login',
                    DB::raw("CONCAT(users.prenom, ' ', users.nom) as name"),
                    DB::raw("'active' as status")
                )
                ->orderBy('user_connections.login_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'users' => $activeUsers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs actifs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Débloque un utilisateur
     */
    public function unblockUser(Request $request, $userId)
    {
        try {
            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ], 404);
            }

            // Débloquer l'utilisateur
            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'statut' => 1,
                    'updated_at' => Carbon::now()
                ]);

            // Logger l'action
            DB::table('admin_actions')->insert([
                'admin_id' => auth()->id(),
                'action_type' => 'unblock_user',
                'target_user_id' => $userId,
                'details' => "Déblocage du compte {$user->email_pro}",
                'created_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur débloqué avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déblocage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bloque un utilisateur
     */
    public function blockUser(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La raison du blocage est requise',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ], 404);
            }

            // Bloquer l'utilisateur
            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'statut' => 0,
                    'updated_at' => Carbon::now()
                ]);

            // Invalider toutes les sessions actives
            DB::table('user_connections')
                ->where('user_id', $userId)
                ->whereNull('logout_at')
                ->update([
                    'logout_at' => Carbon::now(),
                    'session_duration' => DB::raw('TIMESTAMPDIFF(SECOND, login_at, NOW())'),
                    'updated_at' => Carbon::now()
                ]);

            // Logger l'action
            DB::table('admin_actions')->insert([
                'admin_id' => auth()->id(),
                'action_type' => 'block_user',
                'target_user_id' => $userId,
                'details' => "Blocage du compte {$user->email_pro}. Raison: {$request->reason}",
                'created_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur bloqué avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du blocage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Débloque une adresse IP
     */
    public function unblockIP(Request $request, $ipAddress)
    {
        try {
            $block = DB::table('ip_blocks')
                ->where('ip_address', $ipAddress)
                ->first();
            
            if (!$block) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blocage IP introuvable'
                ], 404);
            }

            // Débloquer l'IP
            DB::table('ip_blocks')
                ->where('ip_address', $ipAddress)
                ->delete();

            // Nettoyer les tentatives échouées
            DB::table('failed_login_attempts')
                ->where('ip_address', $ipAddress)
                ->delete();

            // Logger l'action
            DB::table('admin_actions')->insert([
                'admin_id' => auth()->id(),
                'action_type' => 'unblock_ip',
                'target_ip' => $ipAddress,
                'details' => "Déblocage de l'IP {$ipAddress}",
                'created_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IP débloquée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déblocage de l\'IP',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}