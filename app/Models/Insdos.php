<?php

// app/Models/Insdos.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insdos extends Model
{
    use HasFactory;

    protected $table = 'INSDOS';
    protected $primaryKey = 'INPRODID';
}

