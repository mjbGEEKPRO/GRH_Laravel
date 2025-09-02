<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'jhony@gmail.com'], 
            [
                'nom' => 'Mba',
                'prenom' => 'Joseph',
                'email_pro' => 'josephadmingrh@gmail.com',
                'statut' => true,
                'date_naissance' => '2000-12-03',
                'lieu_naissance' => 'yaounde',
                'password' =>  Hash::make('Admin237!'),
                'role_id' => 3,
                'telephone' => '674542312'
            ]
        );
        User::firstOrCreate(
            ['email' => 'christelletetieu@gmail.com'], 
            [
                'nom' => 'FOKOU',
                'prenom' => 'David',
                'email_pro' => 'christellecomptagrh@gmail.com',
                'date_naissance' => '2006-01-03',
                'lieu_naissance' => 'bafoussam',
                'password' => Hash::make('Admin237!'),
                'statut' => true,
                'role_id' => 4,
                'telephone' => '674542312'
            ]
        );

    }
}
