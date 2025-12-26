<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    
    public function index()
    {
        try {
            // Statistiques principales
            $stats = $this->getStats();

            
            // Activité de connexion des 7 derniers jours
            $loginActivity = $this->getLoginActivity();
            
            // Distribution des rôles
            $roleDistribution = $this->getRoleDistribution();
            
            // Logs d'activité récents
            $recentLogs = $this->getRecentLogs();
            
            // Alertes de sécurité
            $securityAlerts = $this->getSecurityAlerts();
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'loginActivity' => $loginActivity,
                'roleDistribution' => $roleDistribution,
                'recentLogs' => $recentLogs,
                'securityAlerts' => $securityAlerts
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupère les statistiques principales
     */
    private function getStats()
    {
        // Total utilisateurs
        $totalUsers = DB::table('users')->count();
        
        // Utilisateurs actifs (qui ont un compte activé)
        $activeUsers = DB::table('users')
            ->where('compte', 1)
            ->where('statut', 1)
            ->count();
        
        // Connexions réussies dans les dernières 24h
        $successfulLogins = DB::table('user_connections')
            ->where('login_at', '>=', Carbon::now()->subDay())
            ->whereNotNull('logout_at')
            ->count();
        
        // Échecs de connexion dans les dernières 24h
        $failedLogins = DB::table('failed_login_attempts')
            ->where('attempted_at', '>=', Carbon::now()->subDay())
            ->count();
        
        // Sessions actives (connectés actuellement)
        $activeSessions = DB::table('user_connections')
            ->whereNotNull('login_at')
            ->whereNull('logout_at')
            ->where('login_at', '>=', Carbon::now()->subHours(12))
            ->count();
        
        // Utilisateurs bloqués (statut = 0)
        $blockedUsers = DB::table('users')
            ->where('statut', 0)
            ->count();
        
        return [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'successfulLogins' => $successfulLogins,
            'failedLogins' => $failedLogins,
            'activeSessions' => $activeSessions,
            'blockedUsers' => $blockedUsers
        ];
    }
    
    /**
     * Récupère l'activité de connexion des 7 derniers jours
     */
    private function getLoginActivity()
    {
        $activity = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->format('d/m');
            
            // Connexions réussies
            $success = DB::table('user_connections')
                ->whereDate('login_at', $date->format('Y-m-d'))
                ->whereNotNull('logout_at')
                ->count();
            
            // Connexions échouées
            $failed = DB::table('failed_login_attempts')
                ->whereDate('attempted_at', $date->format('Y-m-d'))
                ->count();
            
            $activity[] = [
                'date' => $dateStr,
                'success' => $success,
                'failed' => $failed
            ];
        }
        
        return $activity;
    }
    
    /**
     * Récupère la distribution des rôles
     */
    private function getRoleDistribution()
    {
        $roles = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->select('roles.nom as name', DB::raw('COUNT(*) as value'))
            ->whereNotNull('users.role_id')
            ->groupBy('roles.id', 'roles.nom')
            ->get();
        
        return $roles->map(function($role) {
            return [
                'name' => $role->name,
                'value' => (int) $role->value
            ];
        })->toArray();
    }
    
    /**
     * Récupère les logs d'activité récents
     */
    private function getRecentLogs()
    {
        $logs = DB::table('user_connections')
            ->join('users', 'user_connections.user_id', '=', 'users.id')
            ->select(
                'user_connections.login_at',
                'user_connections.logout_at',
                'user_connections.ip_address',
                DB::raw("CONCAT(users.prenom, ' ', users.nom) as user_name")
            )
            ->orderBy('user_connections.login_at', 'desc')
            ->limit(10)
            ->get();
        
        return $logs->map(function($log) {
            $status = 'success';
            $action = 'Connexion réussie';
            
            if (is_null($log->logout_at)) {
                $status = 'warning';
                $action = 'Session active';
            }
            
            return [
                'timestamp' => Carbon::parse($log->login_at)->format('d/m/Y H:i:s'),
                'user' => $log->user_name,
                'action' => $action,
                'ip' => $log->ip_address ?? 'N/A',
                'status' => $status
            ];
        })->toArray();
    }
    
    /**
     * Récupère les alertes de sécurité
     */
    private function getSecurityAlerts()
    {
        $alerts = [];
        
        // Vérifier les connexions multiples échouées
        $suspiciousIPs = DB::table('failed_login_attempts')
            ->select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->where('attempted_at', '>=', Carbon::now()->subHour())
            ->groupBy('ip_address')
            ->having('attempts', '>', 5)
            ->get();
        
        foreach ($suspiciousIPs as $ip) {
            $alerts[] = [
                'severity' => 'critical',
                'title' => 'Tentatives de connexion suspectes',
                'message' => "L'adresse IP {$ip->ip_address} a effectué {$ip->attempts} tentatives de connexion en 1 heure",
                'timestamp' => Carbon::now()->format('d/m/Y H:i:s')
            ];
        }
        
        // Vérifier les utilisateurs récemment bloqués
        $recentlyBlocked = DB::table('users')
            ->where('statut', 0)
            ->where('updated_at', '>=', Carbon::now()->subDay())
            ->count();
        
        if ($recentlyBlocked > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'title' => 'Comptes bloqués récemment',
                'message' => "{$recentlyBlocked} compte(s) ont été bloqués dans les dernières 24 heures",
                'timestamp' => Carbon::now()->format('d/m/Y H:i:s')
            ];
        }
        
        // Vérifier les sessions anormalement longues
        $longSessions = DB::table('user_connections')
            ->where('session_duration', '>', 480) // Plus de 8 heures en minutes
            ->whereNotNull('logout_at')
            ->where('logout_at', '>=', Carbon::now()->subDay())
            ->count();
        
        if ($longSessions > 0) {
            $alerts[] = [
                'severity' => 'info',
                'title' => 'Sessions prolongées détectées',
                'message' => "{$longSessions} session(s) ont duré plus de 8 heures dans les dernières 24 heures",
                'timestamp' => Carbon::now()->format('d/m/Y H:i:s')
            ];
        }
        
        return $alerts;
    }
}