<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    public function index()
{
    return response()->json([
        // Statistiques générales
        'stats' => [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'successfulLogins' => LoginLog::where('status', 'success')
                                        ->where('created_at', '>=', now()->subDay())
                                        ->count(),
            'failedLogins' => LoginLog::where('status', 'failed')
                                    ->where('created_at', '>=', now()->subDay())
                                    ->count(),
            'activeSessions' => DB::table('user_connections')
            ->whereNull('logout_at')
            ->count(),
            'blockedUsers' => User::where('is_blocked', true)->count(),
        ],
        
        // Activité de connexion (7 derniers jours)
        'loginActivity' => [
            ['date' => '2024-01-08', 'success' => 45, 'failed' => 3],
            ['date' => '2024-01-09', 'success' => 52, 'failed' => 5],
            ['date' => '2024-01-10', 'success' => 48, 'failed' => 2],
            ['date' => '2024-01-11', 'success' => 60, 'failed' => 8],
            ['date' => '2024-01-12', 'success' => 55, 'failed' => 4],
            ['date' => '2024-01-13', 'success' => 50, 'failed' => 6],
            ['date' => '2024-01-14', 'success' => 58, 'failed' => 3],
        ],
        
        // Distribution des rôles
        'roleDistribution' => [
            ['name' => 'Admin', 'value' => 5],
            ['name' => 'Manager', 'value' => 12],
            ['name' => 'Employé', 'value' => 45],
            ['name' => 'RH', 'value' => 8],
            ['name' => 'Stagiaire', 'value' => 10],
        ],
        
        // Logs récents (20 derniers)
        'recentLogs' => [
            [
                'timestamp' => '2024-01-14 14:32:15',
                'user' => 'Jean Dupont',
                'action' => 'Connexion réussie',
                'ip' => '192.168.1.105',
                'status' => 'success'
            ],
            [
                'timestamp' => '2024-01-14 14:28:42',
                'user' => 'Marie Martin',
                'action' => 'Modification de permission',
                'ip' => '192.168.1.102',
                'status' => 'success'
            ],
            [
                'timestamp' => '2024-01-14 14:25:18',
                'user' => 'Pierre Dubois',
                'action' => 'Tentative de connexion',
                'ip' => '192.168.1.208',
                'status' => 'failed'
            ],
        ],
        
        // Alertes de sécurité
        'securityAlerts' => [
            [
                'severity' => 'critical',
                'title' => 'Tentatives de connexion suspectes',
                'message' => '5 tentatives échouées détectées depuis l\'IP 192.168.1.208',
                'timestamp' => '2024-01-14 14:25:18'
            ],
            [
                'severity' => 'warning',
                'title' => 'Mot de passe expiré',
                'message' => '3 utilisateurs ont leur mot de passe expiré depuis plus de 90 jours',
                'timestamp' => '2024-01-14 10:00:00'
            ],
        ]
    ]);
}
}
