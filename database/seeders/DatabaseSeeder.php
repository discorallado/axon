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
            ['slug' => 'axon-demo'],
            [
                'name' => 'Axon Demo',
                'email' => 'admin@axon.dev',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@axon.dev'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'organization_id' => $org->id,
            ]
        );

        $this->call(SubmissionStatusSeeder::class);
    }
}
