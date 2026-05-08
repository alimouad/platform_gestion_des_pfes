<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['courriel' => 'mouadalli311@gmail.com'],
            [
                'nom' => 'El Alli',
                'prenom' => 'Mouad',
                'mot_de_passe' => Hash::make('mouad1411'),
                'role' => 'superadmin',
            ]
        );
    }
}
