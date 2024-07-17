<?php

// app/Http/Controllers/InsdosController.php
namespace App\Http\Controllers;

use App\Models\Insdos;
use Illuminate\Http\Request;

class InsdosController extends Controller
{
    public function index()
    {
        // Recuperar todos los datos de la tabla INSDOS con paginación
        $insdosData = Insdos::paginate(10);
        return view('insdos', compact('insdosData'));
    }
}

