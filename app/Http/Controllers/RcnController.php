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
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Obtener los centros de costo asociados al usuario
        $userCostCenters = DB::table('users_cost_centers')
            ->join('store_cost_centers', 'users_cost_centers.center_id', '=', 'store_cost_centers.id')
            ->where('users_cost_centers.user_id', $user->id)
            ->pluck('store_cost_centers.cost_center_id')
            ->toArray();

        // Inicializar variables de datos
        $rcns = collect(); // Inicializar $rcns como colección vacía
        $allDetailedRcns = collect(); // Inicializar $allDetailedRcns como colección vacía
        $filtersApplied = false; // Variable para verificar si se aplicaron filtros

        // Obtener los parámetros de filtro
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $providerName = $request->input('CNCDIRNOM');
        $sortBy = $request->input('sort_by', 'ACMROIDOC');
        $sortOrder = $request->input('sort_order', 'desc');

        // Verificar si algún filtro ha sido aplicado
        $filtersApplied = $startDate || $endDate || $search || $providerName;

        // Si el usuario no tiene centros de costo asociados y no ha aplicado filtros
        if (empty($userCostCenters)) {
            if (!$filtersApplied) {
                // No tiene centros de costo y tampoco ha aplicado filtros
                return view('rcn', compact('rcns', 'allDetailedRcns', 'filtersApplied'))
                    ->with('info', 'Por favor, aplica filtros para ver los registros.');
            } else {
                // No tiene centros de costo, pero ha aplicado filtros, por lo que no encontrará resultados
                return view('rcn', compact('rcns', 'allDetailedRcns', 'filtersApplied'))
                    ->with('warning', 'No se encontraron resultados que coincidan con los filtros aplicados.');
            }
        }

        // Si no se aplican filtros, retornar la vista sin realizar consultas
        if (!$filtersApplied) {
            return view('rcn', compact('rcns', 'allDetailedRcns', 'filtersApplied'))
                ->with('info', 'Por favor, aplica filtros para ver los registros.');
        }

        // Obtener la fecha actual y calcular la fecha de hace 6 meses
        $currentDate = Carbon::now();
        $sixMonthsAgo = $currentDate->copy()->subMonths(6);

        // Si no hay fecha de inicio, establecer hace 6 meses como la fecha mínima
        if (!$startDate) {
            $startDate = $sixMonthsAgo->format('Y-m-d');
        }

        // Si no hay fecha de fin, establecer la fecha actual como fecha máxima
        if (!$endDate) {
            $endDate = $currentDate->format('Y-m-d');
        }

        // Generar una clave única de caché basada en la URL completa para evitar consultas repetidas
        $cacheKey = 'rcns_' . md5($request->fullUrl());

        // Cargar los resultados desde caché
        $rcns = Cache::remember($cacheKey, 60, function () use ($startDate, $endDate, $search, $providerName, $sortBy, $sortOrder, $userCostCenters) {
            $mainQuery = DB::table('ACMROI')
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
                ->leftJoin('store_cost_centers', 'ACMROI.INALMNID', '=', 'store_cost_centers.cost_center_id')
                ->whereIn('store_cost_centers.cost_center_id', $userCostCenters)
                ->whereBetween('ACMROIFREC', [
                    Carbon::parse($startDate)->format('d/m/Y'), // Formatear fecha de inicio
                    Carbon::parse($endDate)->format('d/m/Y')    // Formatear fecha de fin
                ])
                ->where('CNCDIR.CNCDIRID', 'like', '3%')  // Validación: solo mostrar resultados donde CNCDIRID comienza con 3
                ->groupBy('ACMROI.ACMROIDOC', 'CNCDIR.CNCDIRNOM')
                ->orderBy($sortBy, $sortOrder);

            if ($providerName) {
                $mainQuery->where('CNCDIR.CNCDIRNOM', 'LIKE', "%{$providerName}%");
            }

            if ($search) {
                $mainQuery->where('ACMROIDOC', 'LIKE', "%{$search}%");
            }

            return $mainQuery->paginate(10);
        });

        // Pre-obtener los IDs de los ACMROIDOC para evitar consultas repetitivas
        $acmroDocs = collect($rcns->items())->pluck('ACMROIDOC')->toArray();

        // Consultar los detalles adicionales si hay resultados de la consulta principal
        if (!empty($acmroDocs)) {
            $allDetailedRcns = Cache::remember('detailed_rcns_' . md5(implode(',', $acmroDocs)), 60, function () use ($acmroDocs) {
                return DB::table('ACMROI')
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
                    ->whereIn('ACMROIDOC', $acmroDocs)
                    ->where('CNCDIRID', 'like', '3%') // Aplicar validación de CNCDIRID
                    ->where('ACACTLID', '=', '          ') // 10 espacios en blanco en ACACTLID
                    ->where('ACACSGID', '=', '          ') // 10 espacios en blanco en ACACSGID
                    ->where('ACACANID', '=', '          ') // 10 espacios en blanco en ACACANID
                    // No hacemos comparación entre una fecha y un documento, se elimina esa parte
                    ->groupBy('ACMROITDOC', 'ACMROINDOC', 'CNTDOCID', 'ACMROIDOC', 'ACMROIFREC', 'ACACTLID', 'ACACSGID', 'ACACANID')
                    ->get()
                    ->groupBy('ACMROIDOC');
            });
                       
        }

        // Si no se encontraron resultados con los filtros aplicados, mostrar mensaje correspondiente
        if ($rcns->isEmpty()) {
            return view('rcn', compact('rcns', 'allDetailedRcns', 'filtersApplied'))
                ->with('warning', 'No se encontraron resultados que coincidan con los filtros aplicados.');
        }

        return view('rcn', compact('rcns', 'allDetailedRcns', 'filtersApplied'));
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
                ->where('CNCDIRID', 'like', '3%')  // Validación: solo mostrar si CNCDIRID comienza con 3
                ->first();

            if (!$rcn) {
                return redirect()->route('rcn')->with('error', 'RCN no encontrado, cancelado o no cumple con los criterios.');
            }

            // Obtener los registros desde la tabla acmroi utilizando ACMROINDOC y filtrando los que no están cancelados
            $rcns = DB::table('acmroi')
                ->where('ACMROINDOC', $ACMROINDOC)
                ->where('ACACTLID', '!=', 'CANCELADO')
                ->where('ACACSGID', '!=', 'CANCELADO')
                ->where('ACACANID', '!=', 'CANCELADO')
                ->where('CNCDIRID', 'like', '3%')  // Validación: solo mostrar si CNCDIRID comienza con 3
                ->get();

            if ($rcns->isEmpty()) {
                return redirect()->route('rcn')->with('error', 'Orden no encontrada, cancelada o no cumple con los criterios.');
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
