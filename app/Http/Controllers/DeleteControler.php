<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeleteControler extends Controller
{
    public function deleteTeams(Request $request, $projectId)
{
    try {
        Project::findOrFail($projectId);

        $request->validate([
            'team_ids' => 'required|array',
            'team_ids.*' => 'integer|exists:project_teams,id'
        ]);

        Log::info("id teams ". json_encode($request->team_ids));

        $deletedCount = ProjectTeam::where('project_id', $projectId)
            ->whereIn('id', $request->team_ids)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} équipe(s) supprimée(s) avec succès",
            'deleted_count' => $deletedCount
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la suppression:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'data' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la suppression.',
            'debug_info' => config('app.debug') ? [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ] : null
        ], 500);
    }
}
}
