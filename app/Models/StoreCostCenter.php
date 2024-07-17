<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreCostCenter extends Model
{
    protected $table = 'store_cost_centers';

    public function users()
    {
        return $this->hasMany(User::class, 'cost_center_id');
    }
    // app/Models/User.php
public function costCenters()
{
    return $this->hasMany(StoreCostCenter::class, 'user_id'); // Ajusta 'user_id' según tu estructura de base de datos
}

}
