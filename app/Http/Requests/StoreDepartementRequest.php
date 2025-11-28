<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajustez selon vos besoins d'autorisation
    }

    public function rules(): array
    {
        return [
            'nom' => [
                'required',
                'string',
                'max:255',
                'unique:departements,nom'
            ],
            'description' => 'nullable|string|max:1000',
            'postes' => 'required|array|min:1',
            'postes.*' => 'required|string|max:255|distinct'
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du département est obligatoire',
            'nom.unique' => 'Ce département existe déjà',
            'postes.required' => 'Au moins un poste doit être créé',
            'postes.min' => 'Vous devez créer au moins un poste',
            'postes.*.required' => 'Le nom du poste est obligatoire',
            'postes.*.distinct' => 'Les noms de postes doivent être uniques',
        ];
    }
}