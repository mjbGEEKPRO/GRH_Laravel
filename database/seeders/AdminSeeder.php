<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      
      
        User::firstOrCreate(
            ['email' => 'christelletetieu@gmail.com'], 
            [
                'nom' => 'fokou',
                'prenom' => 'david',
                'email_pro' => 'christellecomptagrh@gmail.com',
                'date_naissance' => '2006-01-03',
                'lieu_naissance' => 'bafoussam',
                'password' => Hash::make('Admin237!'),
                'statut' => true,
                'compte' => false,
                'role_id' => 4,
                'telephone' => '674542313'
            ]
        );
        User::firstOrCreate(
            ['email' => 'serdi@gmail.com'], 
            [
                'nom' => 'serdi',
                'prenom' => 'entreprise',
                'email_pro' => 'serdimarketing@gmail.com',
                'date_naissance' => '2001-01-03',
                'lieu_naissance' => 'yaoundé',
                'password' => Hash::make('Admin237!'),
                'statut' => true,
                'compte' => false,
                'role_id' => 5,
                'telephone' => '674542315'
            ]
        );
        $admin =User::firstOrCreate(
            ['email' => 'jhony@gmail.com'], 
            [
                'nom' => 'Mba',
                'prenom' => 'Joseph',
                'email_pro' => 'josephadmingrh@gmail.com',
                'statut' => true,
                'compte' => true,
                'date_naissance' => '2000-12-03',
                'lieu_naissance' => 'yaounde',
                'password' =>  Hash::make('Admin237!'),
                'role_id' => 3,
                'telephone' => '674542312'
            ]
        );

        $admin->permissions()->sync(Permission::all()->pluck('id')->toArray());
    
    }
}
