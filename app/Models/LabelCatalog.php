<?php

// app/Models/LabelCatalog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabelCatalog extends Model
{
    use HasFactory;

    protected $table = 'INPROD';
    protected $primaryKey = 'INPRODID';

    public function insdos()
    {
        return $this->hasOne(Insdos::class, 'INPRODID', 'INPRODID');
    }
}


