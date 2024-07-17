<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AVPREC;

class AVPRECController extends Controller
{
    public function show()
    {
        // Obtener todos los registros de la tabla AVPREC
        $data = AVPREC::all();

        // Pasar los datos a la vista particular
        return view('avprec_show', compact('data'));
    }
}
