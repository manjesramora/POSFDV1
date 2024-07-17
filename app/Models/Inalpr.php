<?php

// app/Models/Inalpr.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inalpr extends Model
{
    use HasFactory;

    protected $table = 'INALPR';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'INPRODID',
        'INALMNID',
        'INAPR17ID',
    ];
}
