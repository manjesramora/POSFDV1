<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Freight;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Dompdf\Dompdf;
use Dompdf\Options;

class FreightController extends Controller
{
    public function index(Request $request)
    {
        $query = Freight::query();

        // Filtros de fecha
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query->whereBetween('reception_date', [$startDate, $endDate]);
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'id'); // Campo predeterminado para ordenar
        $sortOrder = $request->input('sort_order', 'asc');

        if ($sortBy == 'document_type') {
            $query->orderBy('document_type', $sortOrder);
        } elseif ($sortBy == 'document_number') {
            $query->orderBy('document_number', $sortOrder);
        } elseif ($sortBy == 'reception_date') {
            $query->orderBy('reception_date', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $freights = $query->paginate(10)->appends($request->all());

        // Cálculo de totales
        $totalCost = Freight::sum('cost');
        $totalFreight = Freight::sum('freight');
        $totalGeneral = $totalCost + $totalFreight;

        return view('freights', compact('freights', 'totalCost', 'totalFreight', 'totalGeneral'));
    }

    public function generatePDF(Request $request)
    {
        $query = Freight::query();
    
        // Filtros de fecha
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } else {
            // Obtener la fecha más antigua y la más reciente de la base de datos
            $startDate = Freight::min('reception_date');
            $endDate = Freight::max('reception_date');
        }
    
        $query->whereBetween('reception_date', [$startDate, $endDate]);
    
        // Ordenamiento
        $sortBy = $request->input('sort_by', 'id'); // Campo predeterminado para ordenar
        $sortOrder = $request->input('sort_order', 'asc');
    
        if ($sortBy == 'document_type') {
            $query->orderBy('document_type', $sortOrder);
        } elseif ($sortBy == 'document_number') {
            $query->orderBy('document_number', $sortOrder);
        } elseif ($sortBy == 'reception_date') {
            $query->orderBy('reception_date', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }
    
        $freights = $query->get(); // Obtén todos los registros para el PDF
    
        // Cálculo de totales
        $totalCost = $freights->sum('cost');
        $totalFreight = $freights->sum('freight');
        $totalGeneral = $totalCost + $totalFreight;
    
        $pdf = PDF::loadView('report_freights', compact('freights', 'totalCost', 'totalFreight', 'totalGeneral', 'startDate', 'endDate'));
    
        // Reemplazar el marcador de posición de número total de páginas
        $pdf->output();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $text = "Página $pageNumber de $pageCount";
            $font = $fontMetrics->get_font('Arial', 'normal');
            $size = 8;
            $width = $canvas->get_width();
            $height = $canvas->get_height();
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $canvas->text($width - $textWidth - 20, $height - 20, $text, $font, $size);
        });
    
        return $pdf->download('freights_report.pdf');
    }
}
