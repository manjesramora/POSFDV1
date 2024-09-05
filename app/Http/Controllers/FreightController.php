<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Freight;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Auth;

class FreightController extends Controller
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
        $query = Freight::query();
    
        // Filtro por nombre de proveedor (opcional)
        if ($request->filled('CNCDIRNOM')) {
            $providerName = $request->input('CNCDIRNOM');
            $query->whereHas('provider', function ($q) use ($providerName) {
                $q->where('CNCDIRNOM', 'like', '%' . $providerName . '%');
            });
        }
    
        // Filtros de fecha (opcional)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $query->whereBetween('reception_date', [$startDate, $endDate]);
        }
    
        // Ordenamiento
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
    
        // Paginación
        $freights = $query->paginate(10)->appends($request->all());
    
        // Cálculo de totales
        $totalCost = Freight::sum('cost');
        $totalFreight = Freight::sum('freight');
    
        return view('freights', compact('freights', 'totalCost', 'totalFreight'));
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
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
    
        $freights = $query->get(); // Obtén todos los registros para el PDF
    
        // Agrupar por proveedor
        $groupedFreights = $freights->groupBy('supplier_name');
    
        // Cálculo de totales generales
        $totalCost = $freights->sum('cost');
        $totalFreight = $freights->sum('freight');
    
        // Generar el PDF usando el método stream en lugar de download
        $pdf = PDF::loadView('report_freights', compact('groupedFreights', 'totalCost', 'totalFreight', 'startDate', 'endDate'));
    
        // Abrir el PDF en una nueva pestaña (stream)
        return $pdf->stream('freights_report.pdf');
    }    
}
