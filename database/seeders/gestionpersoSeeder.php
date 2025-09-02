<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Poste;
use App\Models\Permission;
use App\Models\Departement;
use App\Models\Role;

class  gestionpersoSeeder extends Seeder
{
    public function run()
    {
        $departements = [
            [
                'nom' => 'Ressources Humaines',
            ],
            [
                'nom' => 'Informatique',
            ],
            [
                'nom' => 'Administration',
            ],
            [
                'nom' => 'Comptabilité',
            ],
            [
                'nom' => 'Marketing',
            ],
            [
                'nom' => 'Marketing',
            ],
        ];

        foreach ($departements as $departement) {
            Departement::create($departement);
        }

        $ressourcesHumainesId = Departement::where('nom', 'Ressources Humaines')->first()->id;
        $informatiqueId = Departement::where('nom', 'Informatique')->first()->id;
        $administrationId = Departement::where('nom', 'Administration')->first()->id;
        $comptabiliteId = Departement::where('nom', 'Comptabilité')->first()->id;
        $marketisteId = Departement::where('nom', 'Marketing')->first()->id;


        $roles = [
            [
                'nom' => 'Secrétaire',
                'departement_id' => $ressourcesHumainesId,
            ],
            [
                'nom' => 'Développeur',
                'departement_id' => $informatiqueId,
            ],
            [
                'nom' => 'Administrateur',
                'departement_id' => $administrationId,
            ],
            [
                'nom' => 'Comptable',
                'departement_id' => $comptabiliteId,
            ],
            [
                'nom' => 'Marketiste',
                'departement_id' => $marketisteId,
            ],
            [
                'nom' => 'Manager',
                'departement_id' => $marketisteId,
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }


        $permissions = [
            [
                'nom' => 'créer_utilisateur',
            ],
            [
                'nom' => 'modifier_utilisateur',
            ],
            [
                'nom' => 'supprimer_utilisateur',
            ],
            [
                'nom' => 'voir_utilisateur',
            ],
            [
                'nom' => 'read',
            ],
            [
                'nom' => 'créer_poste',
            ],
            [
                'nom' => 'modifier_poste',
            ],
            [
                'nom' => 'supprimer_poste',
            ],
            [
                'nom' => 'voir_poste',
            ],
            [
                'nom' => 'attribuer_tache',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
