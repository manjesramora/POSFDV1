<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;
    protected $table = 'users_roles';

    // Definir la relación con el modelo User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Definir la relación con el modelo Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
