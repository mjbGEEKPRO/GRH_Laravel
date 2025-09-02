<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Departement;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    Departement::create(['nom' => 'Informatique', 'poste' => 'Developpeur']);
    Departement::create(['nom' => 'Comptabilité', 'poste' => 'Comptable']);
    Departement::create(['nom' => 'Ressources humaines', 'poste' => 'RH']);
    Departement::create(['nom' => 'Administration', 'poste' => 'Admin']);
    }
}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
