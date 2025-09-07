<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VerificatioController extends Controller
{
    public function verification(Request $request)
    {
        try {
        // Validation des données
        $validation = Validator::make($request->all(), [
            'nom' => ['required', 'string', 'min:2', 'max:30'],
            'prenom' => ['required', 'string', 'max:30', 'min:3'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'regex:/^[a-z][a-z0-9._-]*@gmail.com$/'
            ],
            'telephone' => ['required', 'string', 'regex:/^6\d{8}$/', 'max:9'],
            'poste' => ['required', 'string', 'max:100'],
            'date_naissance' => [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(16)->format('Y-m-d'), 
                'after_or_equal:1927-01-01' 
            ],
            'lieu_naissance' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/' 
            ]
        ]);

        if ($validation->fails()) {
            Log::info('errors'. $validation->errors());
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vérifier vos informations et réessayer',
                'errors' => $validation->errors()
            ], 422);
        }

        // Vérifier si l'utilisateur existe déjà
        $utilisateurExiste = User::where('email', $request->email)->first();

        if ($utilisateurExiste) {
            return response()->json([
                'success' => false,
                'message' => "Un utilisateur avec cette adresse email existe déjà. Si elle vous appartient, veuillez vous connecter."
            ], 422);
        }

          // Vérification des doublons AVANT tout traitement
        $duplicateChecks = [
            'email' => User::where('email', trim($request->email))->first(),
            'telephone' => User::where('telephone', trim($request->telephone))->first(),
        ];
        if ($duplicateChecks['telephone']) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte avec ce numéro de téléphone existe déjà.',
                'field' => 'telephone'
            ], 422);
        }
        return response()->json([
            'success' => true,
            'user' => [
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'dateNaissance' => $request->dateNaissance,
                'lieuNaissance' => $request->lieuNaissance,
                'poste' => $request->poste,
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur validation:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la validation.',
        ], 500);
    }
}
}