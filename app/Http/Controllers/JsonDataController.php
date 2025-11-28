<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class JsonDataController extends Controller
{
    private $filePath;

    public function __construct()
    {
        $this->filePath = storage_path('app/data.json');
        
        // Créer le fichier s'il n'existe pas
        if (!File::exists($this->filePath)) {
            File::put($this->filePath, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function readJson()
    {
        return json_decode(File::get($this->filePath), true) ?? [];
    }

    private function writeJson($data)
    {
        File::put($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    // GET /api/json-data
    public function index()
    {
        $user = User::all();

        
        $departement = null;
    
        
        if ($user->role && $user->role->departement_id) {
            $departement = Departement::find($user->role->departement_id);
              }

        $response = [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'statut' => $user->statut,
                'role' => $user->role ? $user->role->nom : null,
                'role_id' => $user->role_id,
                'departement' => $departement ? $departement->nom : null,
            ]
        ];

        return response()->json($response, 201);
    }

    // POST /api/json-data
    public function store(Request $request)
    {
        

        $data = $this->readJson();
        $newEntry = $request->all();
        $newEntry['id'] = uniqid();
        $newEntry['created_at'] = now()->toISOString();
        
        $data[] = $newEntry;
        $this->writeJson($data);

        return response()->json([
            'success' => true,
            'data' => $newEntry
        ], 201);
    }

    // GET /api/json-data/{id}
    public function show($id)
    {
        $data = $this->readJson();
        
        foreach ($data as $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                return response()->json($item, 200);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Donnée non trouvée'
        ], 404);
    }

    // PUT /api/json-data/{id}
    public function update(Request $request, $id)
    {
        $data = $this->readJson();
        
        foreach ($data as $key => $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                $data[$key] = array_merge($item, $request->all());
                $data[$key]['updated_at'] = now()->toISOString();
                $this->writeJson($data);
                
                return response()->json([
                    'success' => true,
                    'data' => $data[$key]
                ], 200);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Donnée non trouvée'
        ], 404);
    }

    // DELETE /api/json-data/{id}
    public function destroy($id)
    {
        $data = $this->readJson();
        
        foreach ($data as $key => $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                unset($data[$key]);
                $data = array_values($data); // Réindexer
                $this->writeJson($data);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Donnée supprimée'
                ], 200);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Donnée non trouvée'
        ], 404);
    }
}