<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Rcn;
use Illuminate\Support\Facades\Log;

class RcnController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            if ($user) {
                $userRoles = $user->roles;
                view()->share('userRoles', $userRoles);
            }

            return $next($request);
        });
    }

    public function index(Request $request)
{
    // Obtener los parámetros de filtro y ordenación
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $search = $request->input('search');
    $sortBy = $request->input('sort_by', 'ACMROIDOC'); // Campo por defecto para ordenación
    $sortOrder = $request->input('sort_order', 'desc'); // Orden descendente por defecto

    // Inicializar la consulta principal para la tabla agregada
    $mainQuery = DB::table('ACMROI')
        ->select(
            'ACMROIDOC',
            DB::raw('MIN(ACMROITDOC) as ACMROITDOC'),
            DB::raw('MIN(ACMROINDOC) as ACMROINDOC'),
            DB::raw('MIN(CNTDOCID) as CNTDOCID'),
            DB::raw('MIN(ACMROIFREC) as ACMROIFREC'),
            DB::raw('MIN(ACACTLID) as ACACTLID'),
            DB::raw('MIN(ACACSGID) as ACACSGID'),
            DB::raw('MIN(ACACANID) as ACACANID'),
            DB::raw('COUNT(*) as numero_de_partidas'),
            DB::raw('(SELECT COUNT(DISTINCT sub.ACMROINDOC) FROM ACMROI AS sub WHERE sub.ACMROIDOC = ACMROI.ACMROIDOC) as numero_de_rcns')
        )
        ->groupBy('ACMROIDOC')
        ->orderBy($sortBy, $sortOrder);

    // Aplicar filtro de búsqueda si está presente
    if ($search) {
        $mainQuery->where('ACMROIDOC', 'LIKE', "%{$search}%");
    }

    // Aplicar filtros de fechas si ambos están presentes
    if ($startDate && $endDate) {
        $mainQuery->whereBetween('ACMROIFREC', [Carbon::parse($startDate), Carbon::parse($endDate)]);
    }

    // Obtener los resultados paginados para la tabla principal
    $rcns = $mainQuery->paginate(10);

    // Obtener los IDs de los ACMROIDOC para la consulta detallada
    $acmroDocs = collect($rcns->items())->pluck('ACMROIDOC')->toArray();

    // Consulta adicional para los detalles del modal
    $detailedRcns = DB::table('ACMROI')
        ->select(
            'ACMROITDOC',
            'ACMROINDOC',
            'CNTDOCID',
            'ACMROIDOC',
            'ACMROIFREC',
            'ACACTLID',
            'ACACSGID',
            'ACACANID',
            DB::raw('COUNT(*) as numero_de_partidas')
        )
        ->whereIn('ACMROIDOC', $acmroDocs) // Filtrar solo por los ACMROIDOC que se están mostrando en la tabla principal
        ->groupBy(
            'ACMROITDOC',
            'ACMROINDOC',
            'CNTDOCID',
            'ACMROIDOC',
            'ACMROIFREC',
            'ACACTLID',
            'ACACSGID',
            'ACACANID'
        );

    // Aplicar los mismos filtros de búsqueda y fechas a la consulta detallada
    if ($search) {
        $detailedRcns->where('ACMROIDOC', 'LIKE', "%{$search}%");
    }
    if ($startDate && $endDate) {
        $detailedRcns->whereBetween('ACMROIFREC', [Carbon::parse($startDate), Carbon::parse($endDate)]);
    }

    // Ejecutar la consulta detallada y agrupar los resultados por ACMROIDOC
    $allDetailedRcns = $detailedRcns->get()->groupBy('ACMROIDOC');

    // Retornar la vista con los datos necesarios
    return view('rcn', compact('rcns', 'allDetailedRcns', 'startDate', 'endDate', 'sortBy', 'sortOrder', 'search'));
}


public function generatePdf($ACMROINDOC)
{
    try {
        // Buscar el registro utilizando ACMROINDOC y filtrando los que no están cancelados
        $rcn = DB::table('acmroi')
            ->where('ACMROINDOC', $ACMROINDOC)
            ->where('ACACTLID', '!=', 'CANCELADO')
            ->where('ACACSGID', '!=', 'CANCELADO')
            ->where('ACACANID', '!=', 'CANCELADO')
            ->first();

        if (!$rcn) {
            return redirect()->route('rcn')->with('error', 'RCN no encontrado o cancelado.');
        }

        // Obtener los registros desde la tabla acmroi utilizando ACMROINDOC y filtrando los que no están cancelados
        $rcns = DB::table('acmroi')
            ->where('ACMROINDOC', $ACMROINDOC)
            ->where('ACACTLID', '!=', 'CANCELADO')
            ->where('ACACSGID', '!=', 'CANCELADO')
            ->where('ACACANID', '!=', 'CANCELADO')
            ->get();

        if ($rcns->isEmpty()) {
            return redirect()->route('rcn')->with('error', 'Orden no encontrada o todas las partidas están canceladas.');
        }

        // Obtener el número de OL, el ID de la dirección del proveedor, el ID del almacén y el número de referencia
        $numeroOL = $rcn->ACMROIDOC;
        $cncdirid = $rcn->CNCDIRID;
        $almacenId = $rcn->INALMNID;
        $numeroRef = $rcn->ACMROIREF;

        // Obtener el nombre del proveedor utilizando CNCDIRID
        $nombreProveedor = DB::table('CNCDIR')
            ->where('CNCDIRID', $cncdirid)
            ->value('CNCDIRNOM');

        // Lógica para determinar el tipo de referencia basado en ACMROITREF
        $tipoRef = '';
        switch ($rcn->ACMROITREF) {
            case '1':
                $tipoRef = 'FACTURA';
                break;
            case '2':
                $tipoRef = 'REMISION';
                break;
            case '3':
                $tipoRef = 'MISCELANEO';
                break;
            default:
                $tipoRef = 'DESCONOCIDO'; // Opcional: para cualquier otro valor no esperado
                break;
        }

        // Obtener información adicional de 'inprod' para cada registro en 'rcns'
        $rcns = $rcns->map(function ($rcn) {
            $product = DB::table('inprod')
                ->where('INPRODID', $rcn->INPRODID)
                ->select('INPRODI2', 'INPRODI3')
                ->first();

            if ($product) {
                $rcn->INPRODI2 = $product->INPRODI2;
                $rcn->INPRODI3 = $product->INPRODI3;
            } else {
                $rcn->INPRODI2 = null;
                $rcn->INPRODI3 = null;
            }

            return $rcn;
        });

        // Agrupar los datos por CNTDOCID o cualquier otro campo relevante
        $groupedRcns = $rcns->groupBy('CNTDOCID');

        // Generar el PDF utilizando la vista `report_rcn`
        $pdf = PDF::loadView('report_rcn', [
            'groupedRcns' => $groupedRcns,
            'startDate' => $rcns->first()->ACMROIFREC,
            'endDate' => $rcns->first()->ACMROIFREC,
            'numeroRcn' => $rcn->ACMROINDOC, // Número de RCN
            'numeroOL' => $numeroOL, // Número de OL
            'nombreProveedor' => $nombreProveedor, // Nombre del proveedor
            'tipoRef' => $tipoRef, // Tipo de referencia
            'almacenId' => $almacenId, // ID del almacén
            'numeroRef' => $numeroRef // Número de referencia
        ]);

        // Agregar numeración de páginas
        $pdf->output();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $text = "Página $pageNumber de $pageCount";
            $font = $fontMetrics->get_font('Arial', 'normal');
            $size = 8;
            $width = $canvas->get_width();
            $height = $canvas->get_height();
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $canvas->text($width - $textWidth - 20, $height - 20, $text, $font, $size);
        });

        // Mostrar el PDF en el navegador sin descargarlo
        return $pdf->stream('rcn_report_' . $ACMROINDOC . '.pdf');
    } catch (\Exception $e) {
        // Registrar el error para depuración
        Log::error("Error generating PDF: " . $e->getMessage());
        return redirect()->route('rcn')->with('error', 'Error al generar el PDF: ' . $e->getMessage());
    }
}


    
    
}
