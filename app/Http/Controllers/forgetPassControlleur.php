<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;

class forgetPassControlleur extends Controller
{
    public function verifmeil(Request $request)
    {
        try {
            // Validation des données d'entrée
            $validation = Validator::make($request->all(), [
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'regex:/^[a-z][a-z0-9._-]*@gmail.com$/'
                ],
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez vérifier vos informations et réessayer',
                    'errors' => $validation->errors()
                ], 422);
            }

            // Vérifier si l'utilisateur existe
            $user = User::where('email', $request->email)->first();

            //  Vérifier le statut de l'utilisateur
            if (!$user->statut) {
                return response()->json([
                    'success' => false,
                    'message' => "Vous n'avez pas encore été approuver par l'administration"
                ], 403);
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => "Aucun compte ne correspond à votre email vérifier que vous entrez votre adresse mail personnel"
                ], 404);
            }

            
            if ($user && $user->statut) {
                return response()->json([
                    'success' => true,
                    'message' => "Nous allons vous envoyer un code de réinitiaisation dans votre mail personnel",
                    'user'=> $user->id
                ], 200);
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur de connexion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de la vérification de votre émail"
            ], 500);
        }
    }


    public function passReset(Request $request, $userId)
    {
        try {
            
              // Validation des données d'entrée
            $validation = Validator::make($request->all(), [
                'password' => [
                    'required',
                    'min:8',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/\d/',
                    'regex:/[@$!%*#?&]/'
                ]
            ]);

            
            if ($validation->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez vérifier vos informations et réessayer',
                    'errors' => $validation->errors()
                ], 422);
            }


            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $newpass = $request->input('newpass');
            $user->password = $newpass;
            $user->save();

             return response()->json([
                'success' => true,
                'message' => "votre mot de passe a été modifier avec succès",
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}