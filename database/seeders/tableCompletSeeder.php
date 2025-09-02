<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Team;
use App\Models\User;

class TableCompletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['nom' => 'admin']);
        $encadreRole = Role::firstOrCreate(['nom' => 'superviseur']);
        $etudiantRole = Role::firstOrCreate(['nom' => 'stagiaire']);

        $permissions = ['gerer_utilisateurs', 'voir_dashboard', 'editer_contenu', 'soumettre_projet'];
        foreach ($permissions as $permi) {
            Permission::firstOrCreate(['nom' => $permi]);
        }

        $teamA = Team::firstOrCreate(['nom' => 'Equipe A']);
        $teamB = Team::firstOrCreate(['nom' => 'Equipe B']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'], 
            [
                'nom' => 'Toto',
                'prenom' => 'tata',
                'password' => Hash::make('password'),
                'poste' => 'admin',
                'telephone' => '674542312'
            ]
        );

        $etudiant = User::firstOrCreate(
            ['email' => 'etudiant@gmail.com'], 
            [
                'nom' => 'Titi',
                'prenom' => 'tata',
                'password' => Hash::make('password'),
                'poste' => 'etudiant',
                'telephone' => '674542313'
            ]
        );

        $encadreur = User::firstOrCreate(
            ['email' => 'encadreur@gmail.com'], 
            [
                'nom' => 'Tutu',
                'prenom' => 'tata',
                'password' => Hash::make('password'),
                'poste' => 'encadreur',
                'telephone' => '674542314'
            ]
        );

        $etudiant->role()->associate($etudiantRole);
        $etudiant->save();

        $admin->role()->associate($adminRole);
        $admin->save();

        $encadreur->role()->associate($encadreRole);
        $encadreur->save();

        $admin->permissions()->sync(Permission::all()->pluck('id')->toArray());
        $admin->teams()->sync(Team::all()->pluck('id')->toArray());

        $etudiant->permissions()->sync(Permission::where('nom', 'soumettre_projet')->pluck('id')->toArray());
        $etudiant->teams()->sync([$teamA->id]);

        $encadreur->permissions()->sync(Permission::whereIn('nom', ['soumettre_projet', 'voir_dashboard'])->pluck('id')->toArray());
        $encadreur->teams()->sync([$teamA->id]);

        $teamA->permissions()->sync(Permission::all()->pluck('id')->toArray());
        $teamB->permissions()->sync(Permission::where('nom', 'soumettre_projet')->pluck('id')->toArray());
    }
}