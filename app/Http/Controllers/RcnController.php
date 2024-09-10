<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Rcn;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        // Obtener los parámetros de filtro
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $providerName = $request->input('CNCDIRNOM');
        
        // Definir el campo por el que se quiere ordenar y el orden
        $sortBy = $request->input('sort_by', 'ACMROIDOC'); // Por defecto, ordenar por 'ACMROIDOC'
        $sortOrder = $request->input('sort_order', 'desc'); // Por defecto, en orden descendente
        
        // Verificar si se aplicaron filtros
        $filtersApplied = $startDate || $endDate || $search || $providerName;
        
        $rcns = collect(); // Inicializar como vacío si no hay filtros
        $allDetailedRcns = collect(); // Inicializar como vacío si no hay detalles
        
        if ($filtersApplied) {
            // Consultar los datos de RCN con paginación y aplicando los filtros
            $rcns = DB::table('ACMROI')
                ->select(
                    'ACMROI.ACMROIDOC',
                    DB::raw('MIN(ACMROITDOC) as ACMROITDOC'),
                    DB::raw('MIN(ACMROINDOC) as ACMROINDOC'),
                    DB::raw('MIN(CNTDOCID) as CNTDOCID'),
                    DB::raw('MIN(ACMROIFREC) as ACMROIFREC'),
                    DB::raw('COUNT(*) as numero_de_partidas'),
                    DB::raw('COUNT(DISTINCT ACMROINDOC) as numero_de_rcns'),
                    'CNCDIR.CNCDIRNOM'
                )
                ->leftJoin('CNCDIR', 'ACMROI.CNCDIRID', '=', 'CNCDIR.CNCDIRID')
                ->where('CNCDIR.CNCDIRID', 'LIKE', '3%')
                ->groupBy('ACMROI.ACMROIDOC', 'CNCDIR.CNCDIRNOM')
                ->orderBy($sortBy, $sortOrder); // Aplicar el ordenamiento
    
            // Aplicar los filtros
            if ($providerName) {
                $rcns->where('CNCDIR.CNCDIRNOM', 'LIKE', "%{$providerName}%");
            }
    
            if ($search) {
                $rcns->where('ACMROIDOC', 'LIKE', "%{$search}%");
            }
    
            if ($startDate && $endDate) {
                $rcns->whereBetween('ACMROIFREC', [Carbon::parse($startDate), Carbon::parse($endDate)]);
            }
    
            $rcns = $rcns->paginate(10)->appends($request->all());
        }
    
        return view('rcn', compact('rcns', 'allDetailedRcns', 'startDate', 'endDate', 'sortBy', 'sortOrder', 'search', 'providerName', 'filtersApplied'));
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
    
            // Obtener la sucursal (branch) basada en la tabla store_cost_centers
            $branch = DB::table('store_cost_centers')
                ->where('cost_center_id', $almacenId)
                ->select('branch')
                ->first();
    
            // Definir el branch basado en store_id
            $branchName = $branch ? $branch->branch : 'Descripción no disponible';
    
            // Obtener información adicional de 'inprod' para cada registro en 'rcns'
            $rcns = $rcns->map(function ($rcn) {
                $product = DB::table('inprod')
                    ->where('INPRODID', $rcn->INPRODID)
                    ->select('INPRODI2', 'INPRODCBR')
                    ->first();
    
                $rcn->INPRODI2 = $product->INPRODI2 ?? null;
                $rcn->INPRODCBR = $product->INPRODCBR ?? null;
    
                return $rcn;
            });
    
            // Agrupar los datos por CNTDOCID o cualquier otro campo relevante
            $groupedRcns = $rcns->groupBy('CNTDOCID');
    
            // Obtener la fecha de impresión y la fecha de elaboración (ACMROIFREC)
            $fechaElaboracion = \Carbon\Carbon::parse($rcn->ACMROIFREC)->format('d/m/Y');
            $fechaImpresion = \Carbon\Carbon::now()->format('d/m/Y');
    
            // Generar el PDF utilizando la vista `report_rcn`
            $pdf = PDF::loadView('report_rcn', [
                'groupedRcns' => $groupedRcns,
                'fechaElaboracion' => $fechaElaboracion,
                'fechaImpresion' => $fechaImpresion,
                'numeroRcn' => $rcn->ACMROINDOC,
                'numeroOL' => $numeroOL,
                'nombreProveedor' => $nombreProveedor,
                'branchName' => $branchName,  // Aquí se pasa el nombre de la sucursal
                'tipoRef' => $this->getTipoReferencia($rcn->ACMROITREF),
                'almacenId' => $almacenId,
                'numeroRef' => $numeroRef
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
    

    private function getTipoReferencia($ref)
    {
        switch ($ref) {
            case '1':
                return 'FACTURA';
            case '2':
                return 'REMISION';
            case '3':
                return 'MISCELANEO';
            default:
                return 'DESCONOCIDO';
        }
    }
}
