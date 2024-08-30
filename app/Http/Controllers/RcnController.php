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
        $sortBy = $request->input('sort_by', 'ACMROIDOC'); // Campo por defecto para ordenación
        $sortOrder = $request->input('sort_order', 'desc'); // Orden descendente por defecto
    
        // Construir la consulta base para la tabla ACMROI
        $query = DB::table('ACMROI')
            ->select(
                'ACMROITDOC',
                'ACMROINDOC',
                'CNTDOCID',
                'ACMROIDOC',
                'ACMROIFREC',
                'ACACTLID',  // Asegúrate de seleccionar estos campos
                'ACACSGID',  // Asegúrate de seleccionar estos campos
                'ACACANID',  // Asegúrate de seleccionar estos campos
                DB::raw('COUNT(*) as numero_de_partidas'),
                DB::raw('(SELECT COUNT(DISTINCT sub.ACMROINDOC) FROM ACMROI AS sub WHERE sub.ACMROIDOC = ACMROI.ACMROIDOC) as numero_de_rcns') // Contar número de RCNs distintas
            )
            ->groupBy('ACMROITDOC', 'ACMROINDOC', 'CNTDOCID', 'ACMROIDOC', 'ACMROIFREC', 'ACACTLID', 'ACACSGID', 'ACACANID') // Agrega estos campos en el groupBy
            ->orderBy($sortBy, $sortOrder); // Ordenar por el campo seleccionado en orden descendente
    
        // Aplicar filtros de fechas si están presentes
        if ($startDate) {
            $query->whereDate('ACMROIFREC', '>=', Carbon::parse($startDate));
        }
        if ($endDate) {
            $query->whereDate('ACMROIFREC', '<=', Carbon::parse($endDate));
        }
    
        // Obtener los resultados paginados
        $rcns = $query->paginate(10);
    
        // Retornar la vista con los datos necesarios
        return view('rcn', compact('rcns', 'startDate', 'endDate', 'sortBy', 'sortOrder'));
    }    

    public function generatePdf($ACMROINDOC)
    {
        try {
            // Buscar el registro utilizando ACMROINDOC
            $rcn = DB::table('acmroi')->where('ACMROINDOC', $ACMROINDOC)->first();
    
            if (!$rcn) {
                return redirect()->route('rcn')->with('error', 'RCN no encontrado.');
            }
    
            // Obtener los registros desde la tabla acmroi utilizando ACMROINDOC
            $rcns = DB::table('acmroi')
                ->where('ACMROINDOC', $ACMROINDOC)
                ->get();
    
            if ($rcns->isEmpty()) {
                return redirect()->route('rcn')->with('error', 'Orden no encontrada.');
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
                'endDate' => $rcns->first()->ACMROIFREC
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
    
            return $pdf->download('rcn_report_' . $ACMROINDOC . '.pdf'); // Descargar el PDF
    
        } catch (\Exception $e) {
            // Registrar el error para depuración
            Log::error("Error generating PDF: " . $e->getMessage());
            return redirect()->route('rcn')->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }
    
}
