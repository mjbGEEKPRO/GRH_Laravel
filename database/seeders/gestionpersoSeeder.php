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
                'nom' => 'users.view',
                'slug' => 'users_view',
                'description'=>'Permet de voir la liste des employés',
            ],
            [
                'nom' => 'users.create',
                'slug' => 'users_create',
                'description'=>'Permet de créer un employer et d/approuver les employés'
            ],
            [
                'nom' => 'users.update',
                'slug' => 'users_update',
                'description'=>'Permet de modifier l/email professionnel et le poste de l/employé'
            ],
            [
                'nom' => 'users.delete',
                'slug' => 'users_delete',
                'description'=>'Permet de retirer un employé du system'
            ],
            [
                'nom' => 'users.manage',
                'slug' => 'users_manage',
                'description'=>'Permet le management des employés'
            ],
            [
                'nom' => 'task.assign',
                'slug' => 'task_assign',
                'description'=>'Permet d/assigner des tâches aux employés'
            ],
            [
                'nom' => 'projects.manage',
                'slug' => 'projects_manage',
                'description'=>'Permet le management générale des projet'
            ],
            [
                'nom' => 'projects.assign',
                'slug' => 'projects_assign',
                'description'=>'Permet d/assigner des projet à des département'
            ],
            [
                'nom' => 'projects.create',
                'slug' => 'projects_create',
                'description'=>'Permet de creer des projets'
            ],
            [
                'nom' => 'projects.update',
                'slug' => 'projects_update',
                'description'=>'Permet la mise à jour du statut d/un projet'
            ],
            [
                'nom' => 'projects.delete',
                'slug' => 'projects_delete',
                'description'=>'Permet de supprimer un projet'
            ],
            [
                'nom' => 'projects.view',
                'slug' => 'projects_view',
                'description'=>'Permet de voir les projets de l/entreprise'
            ],
            [
                'nom' => 'tasks.view',
                'slug' => 'tasks_view',
                'description'=>'Permet de voir les tâches des employers'

            ],
            [
                'nom' => 'connexion.history',    
                'slug' => 'connexion_history',
                'description'=>'Permet de voir l/historique de connexion'    
            ],
            [
                'nom' => 'admin.access',
                'slug' => 'admin_access',
                'description'=>'Permet d/acceder à l/espace administrateur'
            ],
            [
                'nom' => 'permissions.view',
                'slug' => 'permissions_view',
                'description'=>'Permet d/avoir accès  l/espace de management générale des employées'
            ],
            [
                'nom' => 'admin.dashboard',
                'slug' => 'admin_dashboard',
                'description'=>'Permet de voir le dasboard de l/administrateur'
            ],
            [
                'nom' => 'reports.view',
                'slug' => 'reports_view',
            ],
            [
                'nom' => 'admin.settings',
                'slug' => 'admin_settings',
                'description'=>'Permet d/acceder aux paramètres de l/administrateur'
            ],
            [
                'nom' => 'my.tasks',
                'slug' => 'my_tasks',
                'description'=>'Permet à l/employé de voir ses tâches'
            ],
            [
                'nom' => 'my.projects',
                'slug' => 'my_projects',
                'description'=>'Permet à l/employé de voir les projets dans lesquel il intervient'
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
