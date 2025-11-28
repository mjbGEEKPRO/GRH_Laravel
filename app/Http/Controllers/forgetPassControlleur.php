<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class forgetPassControlleur extends Controller
{   
    public function verifmeil(Request $request)
    {
        try {

            Log::info("mail recu ". json_encode($request->email));
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
            Log::info("email apres".  $request->input('email'));
            // Vérifier si l'utilisateur existe
            $user = User::where('email', $request->input('email'))->first();

             if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => "Aucun compte ne correspond à votre email vérifier que vous entrez votre adresse mail personnel"
                ], 404);
            }

            //  Vérifier le statut de l'utilisateur
            if (!$user->statut) {
                return response()->json([
                    'success' => false,
                    'message' => "Vous n'avez pas encore été approuver par l'administration"
                ], 403);
            }

           

            
            if ($user && $user->statut) {
                Log::info("id du user". $user->id);
                return response()->json([
                    'success' => true,
                    'message' => "Nous allons vous envoyer un code de réinitiaisation dans votre mail personnel",
                    'id'=> $user->id,
                    'nom'=> $user->nom,
                ], 200);
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'inscription:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'data' => $request->all() // Log des données reçues pour debug
        ]);
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de la vérification de votre émail"
            ], 500);
        }
    }


    public function passReset(Request $request, $userId)
    {
        try {
            Log::info("id user pass ". json_encode($request->password));
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
                    'message' => "Aucun compte n'a les informations que vous avez renseigner"
                ], 404);
            }

            //  Vérifier le statut de l'utilisateur
            if (!$user->statut) {
                return response()->json([
                    'success' => false,
                    'message' => "Votre demande n'a pas encore été approuvée"
                ], 403);
            }

            $newpass = $request->input('password');
            $user->password = Hash::make($newpass);
            $user->save();

             return response()->json([
                'success' => true,
                'message' => "votre mot de passe a été modifier avec succès",
            ], 200);
        } catch (\Exception $e) {
              Log::error('Erreur lors de l\'inscription:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'data' => $request->all() // Log des données reçues pour debug
        ]);
        return response()->json([
                'success' => false,
                'message' => "Erreur lors de la modification de votre mot de passe"
            ], 500);
        }
    }
}