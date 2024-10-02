<?php

namespace App\Http\Controllers;

use App\Models\AssortmentSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Asegúrate de incluir esto para usar consultas directas
use Carbon\Carbon; // Importar Carbon para manejar fechas

class AssortmentController extends Controller
{
    public function index()
    {
        // Obtenemos la fecha de hoy
        $today = Carbon::today();

        // Obtenemos los datos de la tabla AVMVOR donde la fecha es hoy
        $assortmentSheets = AssortmentSheet::select('INALMNID', 'CNCIASID', 'CNTDOCID', 'AVMVORDOC', 'AVMVORFSPC', 'AVMVORCXC')
            ->whereDate('AVMVORFSPC', $today) // Filtramos solo los registros de hoy
            ->paginate(10); // Paginamos los resultados

        // Ahora, vamos a obtener el nombre del cliente de la tabla CNCDIR
        // Primero, obtenemos los IDs de cliente en un array
        $clientIds = $assortmentSheets->pluck('AVMVORCXC')->toArray();

        // Obtenemos los nombres de los clientes que correspondan a esos IDs
        $clients = DB::table('CNCDIR')
            ->whereIn('CNCDIRID', $clientIds)
            ->pluck('CNCDIRNOM', 'CNCDIRID'); // Aquí estamos ploteando el nombre del cliente usando su ID

        // Pasamos los datos a la vista
        return view('assortment_sheets', compact('assortmentSheets', 'clients'));
    }
}
