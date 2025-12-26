<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Barryvdh\DomPDF\Facade\Pdf;

class SecurityDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getConnectionHistory(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        if ($user->role->nom !== "Administrateur") {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_id' => 'nullable|integer|exists:users,id',
            'departement' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = DB::table('user_connections as uc')
                    ->join('users as u', 'uc.user_id', '=', 'u.id')
                    ->join('roles as r', 'u.role_id', '=', 'r.id')
                    ->join('departements as d', 'r.departement_id', '=', 'd.id')
                    ->select([
                        'uc.id',
                        'u.nom',
                        'u.prenom', 
                        'u.email_pro',
                        'r.nom as roles',
                        'd.nom as departement',
                        'uc.login_at',
                        'uc.logout_at',
                        'uc.ip_address',
                        'uc.user_agent',
                        'uc.session_duration',
                        'uc.created_at'
                    ])
                    ->orderBy('uc.login_at', 'desc');

        if ($request->date_from) {
            $query->whereDate('uc.login_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('uc.login_at', '<=', $request->date_to);
        }

        if ($request->user_id) {
            $query->where('u.id', $request->user_id);
        }

        if ($request->departement) {
            $query->where('d.nom', $request->departement);
        }

        if (!$request->date_from && !$request->date_to) {
            $query->where('uc.login_at', '>=', now()->subDays(30));
        }

        $perPage = $request->per_page ?? 50;
        $connections = $query->paginate($perPage);

        $stats = [
            'total_connections_today' => DB::table('user_connections')
                ->whereDate('login_at', today())
                ->count(),
            
            'unique_users_today' => DB::table('user_connections')
                ->whereDate('login_at', today())
                ->distinct('user_id')
                ->count(),
            
            'avg_session_duration' => DB::table('user_connections')
                ->whereNotNull('session_duration')
                ->whereDate('login_at', today())
                ->avg('session_duration'),
                
            'total_connections_this_week' => DB::table('user_connections')
                ->whereBetween('login_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $connections->items(),
            'pagination' => [
                'current_page' => $connections->currentPage(),
                'last_page' => $connections->lastPage(), 
                'per_page' => $connections->perPage(),
                'total' => $connections->total(),
                'from' => $connections->firstItem(),
                'to' => $connections->lastItem()
            ],
            'stats' => $stats
        ]);
    }

    /**
     * Export CSV avec encodage UTF-8 BOM et séparateur point-virgule
     */
    public function exportConnectionHistory(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        if ($user->role->nom !== "Administrateur") {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'departement' => 'nullable|string',
            'format' => 'nullable|string|in:csv,pdf'
        ]);

        $query = DB::table('user_connections as uc')
                    ->join('users as u', 'uc.user_id', '=', 'u.id')
                    ->join('roles as r', 'u.role_id', '=', 'r.id')
                    ->join('departements as d', 'r.departement_id', '=', 'd.id')
                    ->select([
                        'u.nom',
                        'u.prenom',
                        'u.email_pro',
                        'd.nom as departement',
                        'r.nom as poste',
                        'uc.login_at',
                        'uc.logout_at',
                        'uc.session_duration',
                        'uc.ip_address'
                    ])
                    ->orderBy('uc.login_at', 'desc');

        if ($request->date_from) {
            $query->whereDate('uc.login_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('uc.login_at', '<=', $request->date_to);
        }

        if ($request->departement) {
            $query->where('d.nom', $request->departement);
        }

        $connections = $query->get();

        $format = $request->format ?? 'csv';

        if ($format === 'pdf') {
            return $this->exportToPDF($connections, $request);
        }

        return $this->exportToCSV($connections);
    }

    /**
     * Export CSV avec BOM UTF-8 et délimiteur point-virgule pour Excel
     */
    private function exportToCSV($connections)
    {
        $filename = 'historique_connexions_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($connections) {
            $file = fopen('php://output', 'w');
            
            // IMPORTANT: Ajouter le BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes CSV avec point-virgule comme séparateur
            fputcsv($file, [
                'Nom',
                'Prénom',
                'Email professionnel',
                'Département',
                'Poste',
                'Date de connexion',
                'Heure de connexion',
                'Date de déconnexion',
                'Heure de déconnexion',
                'Durée de session (min)',
                'Adresse IP'
            ], ';'); // Point-virgule pour Excel français
                        
            foreach ($connections as $connection) {
                $loginDate = $connection->login_at ? date('d/m/Y', strtotime($connection->login_at)) : '';
                $loginTime = $connection->login_at ? date('H:i:s', strtotime($connection->login_at)) : '';
                $logoutDate = $connection->logout_at ? date('d/m/Y', strtotime($connection->logout_at)) : '';
                $logoutTime = $connection->logout_at ? date('H:i:s', strtotime($connection->logout_at)) : '';
                $duration = $connection->session_duration ? round($connection->session_duration / 60, 2) : '';
                
                fputcsv($file, [
                    $connection->nom,
                    $connection->prenom,
                    $connection->email_pro,
                    $connection->departement,
                    $connection->poste,
                    $loginDate,
                    $loginTime,
                    $logoutDate,
                    $logoutTime,
                    $duration,
                    $connection->ip_address ?? ''
                ], ';'); // Point-virgule pour Excel français
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export PDF avec logo en filigrane
     */
    private function exportToPDF($connections, $request)
    {
        $data = [
            'connections' => $connections,
            'date_generation' => now()->format('d/m/Y à H:i'),
            'periode' => $this->getPeriodeText($request),
            'total' => $connections->count()
        ];

        $pdf = PDF::loadView('exports.connection-history', $data);
        
        // Configuration PDF
        $pdf->setPaper('a4', 'landscape'); // Paysage pour plus de colonnes
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);
        $pdf->setOption('margin-right', 10);

        $filename = 'historique_connexions_' . date('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    private function getPeriodeText($request)
    {
        if ($request->date_from && $request->date_to) {
            return 'Du ' . date('d/m/Y', strtotime($request->date_from)) . 
                   ' au ' . date('d/m/Y', strtotime($request->date_to));
        } elseif ($request->date_from) {
            return 'Depuis le ' . date('d/m/Y', strtotime($request->date_from));
        } elseif ($request->date_to) {
            return 'Jusqu\'au ' . date('d/m/Y', strtotime($request->date_to));
        }
        
        return 'Derniers 30 jours';
    }
}