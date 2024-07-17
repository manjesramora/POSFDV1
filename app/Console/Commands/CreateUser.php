<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Crear un nuevo usuario';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $username = $this->ask('Nombre de usuario');
        $password = $this->secret('Contraseña');

        $user = new User();
        $user->username = $username;
        $user->password = Hash::make($password);
        $user->employee_id = 1; // Asignar un empleado por defecto o puedes personalizarlo
        $user->status = 1; // Activo por defecto
        $user->save();

        $this->info('Usuario creado correctamente.');
    }
}
