<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Rcn;

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
        $sortBy = $request->input('sort_by', 'ACMROINDOC'); // Campo por defecto para ordenación
        $sortOrder = $request->input('sort_order', 'asc');

        // Construir la consulta base para la tabla ACMROI
        $query = DB::table('ACMROI')
            ->select('ACMROITDOC', 'ACMROINDOC', 'CNTDOCID', 'ACMROIDOC', 'ACMROIFREC', DB::raw('COUNT(*) as numero_de_partidas'))
            ->groupBy('ACMROITDOC', 'ACMROINDOC', 'CNTDOCID', 'ACMROIDOC', 'ACMROIFREC')
            ->orderBy($sortBy, $sortOrder);

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
        // Buscar el registro utilizando ACMROINDOC
        $rcn = Rcn::where('ACMROINDOC', $ACMROINDOC)->first();

        if (!$rcn) {
            return redirect()->route('rcn')->with('error', 'RCN no encontrado.');
        }

        // Agrupar los datos por CNTDOCID o cualquier otro campo relevante
        $groupedRcns = collect([$rcn])->groupBy('CNTDOCID');

        // Generar el PDF utilizando la vista `report_rcn`
        $pdf = PDF::loadView('report_rcn', [
            'groupedRcns' => $groupedRcns,
            'startDate' => $rcn->ACMROIFREC,
            'endDate' => $rcn->ACMROIFREC
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

        return $pdf->download('rcn_report_' . $ACMROINDOC . '.pdf');
    }
}
