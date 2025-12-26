<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:old-logs 
                            {--days=90 : Nombre de jours à conserver}
                            {--failed-attempts-hours=24 : Heures à conserver pour les tentatives échouées}
                            {--dry-run : Simuler sans supprimer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie les anciens logs de connexion et tentatives échouées';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $failedAttemptsHours = (int) $this->option('failed-attempts-hours');
        $dryRun = $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days);
        $failedAttemptsCutoff = Carbon::now()->subHours($failedAttemptsHours);
        
        $this->info("Nettoyage des logs antérieurs au " . $cutoffDate->format('d/m/Y'));
        
        if ($dryRun) {
            $this->warn("Mode simulation activé - Aucune suppression ne sera effectuée");
        }
        
        // Nettoyer les connexions utilisateur
        $connectionsCount = DB::table('user_connections')
            ->where('login_at', '<', $cutoffDate)
            ->count();
        
        $this->info("Connexions à supprimer: {$connectionsCount}");
        
        if (!$dryRun && $connectionsCount > 0) {
            DB::table('user_connections')
                ->where('login_at', '<', $cutoffDate)
                ->delete();
            $this->info("Connexions supprimées");
        }
        
        // Nettoyer les tentatives échouées (plus agressif - 24h par défaut)
        $failedCount = DB::table('failed_login_attempts')
            ->where('attempted_at', '<', $failedAttemptsCutoff)
            ->count();
        
        $this->info("📊 Tentatives échouées à supprimer (> {$failedAttemptsHours}h): {$failedCount}");
        
        if (!$dryRun && $failedCount > 0) {
            DB::table('failed_login_attempts')
                ->where('attempted_at', '<', $failedAttemptsCutoff)
                ->delete();
            $this->info("✅ Tentatives échouées supprimées");
        }
        
        $totalDeleted = $connectionsCount + $failedCount;
        
        if ($dryRun) {
            $this->info("Total qui serait supprimé: {$totalDeleted} enregistrements");
            $this->info("Lancez sans --dry-run pour effectuer la suppression");
        } else {
            $this->info("Nettoyage terminé! {$totalDeleted} enregistrements supprimés");
        }
        
        // Optimiser les tables
        if (!$dryRun) {
            $this->info("Optimisation des tables...");
            DB::statement('OPTIMIZE TABLE user_connections');
            DB::statement('OPTIMIZE TABLE failed_login_attempts');
            $this->info("Tables optimisées");
        }
        
        return Command::SUCCESS;
    }
}