<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class changePassController extends Controller
{
    public function passReset(Request $request, $userId)
    {
        try {
              // Validation des données d'entrée
            $validation = Validator::make($request->all(), [
                'currentPassword' => [
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

            $verifpass= $user->password;

            if (! Hash::check($request->currentPassword,$verifpass))
            {
                 return response()->json([
                    'success' => false,
                    'message' => "Vérifier le précédent mot de passe"
                ], 403);
            }
            $newpass = $request->input('newPassword');
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
