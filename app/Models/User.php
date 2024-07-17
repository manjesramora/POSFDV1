<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'password',
        'employee_id',
        'status',
        'locked',
        'failed_login_attempts', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employee associated with the user.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function costCenters()
    {
        return $this->belongsToMany(StoreCostCenter::class, 'users_cost_centers','user_id','center_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(StoreCostCenter::class, 'store_cost_centers_id');
    }

    /**
     * Get the roles associated with the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }
    public function setUsernameAttribute($value)
    {
        // Aquí, estamos definiendo un mutador para el atributo "username".
        // Siempre que se asigne un valor al atributo "username", este método será llamado.
        // Convertir el valor de entrada a mayúsculas.
        $this->attributes['username'] = strtoupper($value);
        
    }

    public function hasPermission($permissionName)
    {
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permissionName)) {
                return true;
            }
        }
        return false;
    }
    

}
