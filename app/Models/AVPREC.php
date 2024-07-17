<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AVPREC extends Model
{
    use HasFactory;

    // Definir el nombre de la tabla
    protected $table = 'AVPREC';

    // Definir las columnas que se pueden llenar
    protected $fillable = [
        'AVPRECPRO',
        'AVPRECBAS',
        'AVPREFIPB',
        'AVPRECALM',
    ];

    // Si no usas timestamps, puedes desactivar la característica
    public $timestamps = false;
}
