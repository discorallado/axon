<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $org = Organization::updateOrCreate(
            ['slug' => 'cse-soluciones-industriales'],
            [
                'name' => 'CSE Soluciones Industriales',
                'email' => 'sebastian.alvarez@csenergy.cl',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'sebastian.alvarez@csenergy.cl'],
            [
                'name' => 'Sebastián Álvarez Cabezas',
                'password' => Hash::make('password'),
                'organization_id' => $org->id,
            ]
        );

        $this->call([
            FormTemplateSeeder::class,
        ]);
    }
}
