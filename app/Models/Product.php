<?php
// Archivo: app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'INPROD';
    protected $primaryKey = 'INPRODID';

    // Definir la relación inversa con el modelo Order
    public function orders()
    {
        return $this->hasMany(Order::class, 'INPRODID', 'INPRODID');
    }
}
