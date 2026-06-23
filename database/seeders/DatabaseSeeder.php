<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Crear u obtener la organización
        $org = Organization::updateOrCreate(
            ['slug' => 'cse-soluciones-industriales'],
            [
                'name' => 'CSE Soluciones Industriales',
                'email' => 'sebastian.alvarez@csenergy.cl',
                'is_active' => true,
            ]
        );

        // 2. Forzar la creación del rol super_admin para evitar el error de Spatie
        $superAdminName = config('filament-shield.super_admin.name', 'super_admin');
        Role::firstOrCreate(['name' => $superAdminName, 'guard_name' => 'web']);

        // 3. Crear el resto de tus roles personalizados
        $roles = ['ingeniero', 'supervisor', 'calidad', 'tecnico'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 4. Ejecutar comandos de Filament Shield para generar las políticas y permisos de tus recursos
        $this->command->call('shield:generate', ['--option' => 'no-interaction']);

        // 5. Crear el usuario administrador
        $user = User::updateOrCreate(
            ['email' => 'sebastian.alvarez@csenergy.cl'],
            [
                'name' => 'Sebastián Álvarez Cabezas',
                'password' => Hash::make('password'),
                'organization_id' => $org->id,
            ]
        );

        // 6. Asignar el rol de super_admin al usuario (ahora garantizado que existe)
        $user->assignRole($superAdminName);

        // 7. Llamar a otros seeders existentes
        // $this->call([
        //     // Otros seeders que quieras ejecutar
        // ]);
        
        $this->command->info('¡Base de datos sembrada, roles creados y Super Admin asignado con éxito!');
    }
}
