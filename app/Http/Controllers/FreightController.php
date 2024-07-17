<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Freight;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

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

        return view('freights', compact('freights'));
    }

    public function generatePDF(Request $request)
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
    
        $freights = $query->get(); // Obtén todos los registros para el PDF
    
        $pdf = PDF::loadView('report_freights', compact('freights'));
        return $pdf->download('freights_report.pdf');
    }
    
}
