<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;

class settingController extends Controller
{
    public function personal_info(Request $request, $id)
    {

         $validation = Validator::make($request->all(), [
            'adresse' => [
                
                'string',
                'email',
                'max:255',
                'regex:/^[a-z][a-z0-9._-]*@gmail.com$/'
            ],
        ]);
        Log::info("mail ". $validation->errors());
        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => "veillez vérifier le format de votre adresse Email",
                'errors' => $validation->errors()
            ], 422);
        }

        try{


            $user =User::find($id)->first();

            if ($request->telephone == $user->telephone)
            {
                return response()->json([
            'success' => false,
            'message' => 'Le nouveau numéro de télephone correspond au précedant',
                'errors' => $validation->errors()
            ], 422);
            }


            $user->telephone = $request->telephone;
            $user->email = $request->adresse;
            $user->situation_famille = $request->situationFamiliale;
            $user->save();
             return response()->json([
                'success' => true,
                'message' => 'Vos informations personnel ont été mis à jour',
            ], 422);

        } catch  (\Exception $e)
    {
         Log::error('Erreur lors de la modification:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'data' => $request->all() // Log des données reçues pour debug
        ]);

         return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenu lors de la modification des infomationss',
                'errors' => $validation->errors()
            ], 500);
    };
}

    public function preferences(Request $request, $id)
    {
        Log::info("arriver ici ");

        $user = Auth::user();
        $preferences = $user->preferences;
        Log::info("arriver ici ");
        if (!$preferences) {
            return response()->json([
                'success' => false,
                'message' => 'Préférences non trouvées'
            ], 404);
        }
                Log::info("arriver ici avant validation ");

        // Validation
        $validator = Validator::make($request->all(), [
            'preferences.notifications.email' => 'sometimes|boolean',
            'preferences.notifications.taskReminders' => 'sometimes|boolean',
            'preferences.notifications.projectUpdates' => 'sometimes|boolean',
            'preferences.notifications.deadlineAlerts' => 'sometimes|boolean',
            'preferences.language' => 'sometimes|string|in:fr,en,wo',
            'preferences.autoLogout' => 'sometimes|integer|min:5|max:240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }
                Log::info("arriver ici entrez try ");

            try{
                
                // Mise à jour des préférences
                $data = [];

                if ($request->has('preferences.notifications')) {
                    $notifs = $request->input('preferences.notifications');
                    $data['notif_email'] = $notifs['email'] ?? $preferences->notif_email;
                    $data['notif_task_reminders'] = $notifs['taskReminders'] ?? $preferences->notif_task_reminders;
                    $data['notif_project_updates'] = $notifs['projectUpdates'] ?? $preferences->notif_project_updates;
                    $data['notif_deadline_alerts'] = $notifs['deadlineAlerts'] ?? $preferences->notif_deadline_alerts;
                }

                    if ($request->has('preferences.language')) {
                        $data['language'] = $request->input('preferences.language');
                    }

                    if ($request->has('preferences.autoLogout')) {
                        $data['auto_logout'] = $request->input('preferences.autoLogout');
                    }

Log::info("auto logout recu ",  $data['auto_logout']);
                     $preferences->update($data);
                return response()->json([
                    'success'=>true,
                    'message'=>'Vos préferences ont été mis à jour',
                ], 200);
            }catch (\Exception $e) {
                Log::error('Erreur lors de la mise à jour:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'data' => $request->all() // Log des données reçues pour debug
        ]);
                return response()->json([
                    'success'=>false,
                    'message'=>'Une erreur est survenue lors de l/enregistrement des préferences',
                ], 500);
            }
    }


    public function compte(Request $request, $id){
        $user =User::find($id)->first();
        Log::info("compte status ". $user);
        try{ 
        if ($user->compte)
        {
             $user->compte=false;
             $user->save();
        }else{

             $user->compte=true;
             $user->save();
        }
       
        Log::info("compte status2 ". $user->compte);

        return response()->json([
            'success'=>true,
            'message'=>'Vous allez être désactiver'
        ], 200);
    }catch(\Exception $e) {

        return response()->json([
            'success'=>true,
            'message'=>'Une erreur est survenue'
        ], 500);

    }
    }
}