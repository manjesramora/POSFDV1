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

        // Verificar si se aplicaron filtros
        $filtersApplied = $request->filled('CNCDIRNOM') || $request->filled('CNCDIRNOM_TRANSP') || ($request->filled('start_date') && $request->filled('end_date'));

        // Si no se aplican filtros, retornar una colección vacía
        if (!$filtersApplied) {
            $freights = collect(); // Colección vacía
            return view('freights', compact('freights'));
        }

        // Filtro por nombre de proveedor
        if ($request->filled('CNCDIRNOM')) {
            $providerName = $request->input('CNCDIRNOM');
            $query->whereHas('provider', function ($q) use ($providerName) {
                $q->where('CNCDIRNOM', 'like', '%' . $providerName . '%');
            });
        }

        // Filtro por nombre de transportista
        if ($request->filled('CNCDIRNOM_TRANSP')) {
            $transporterName = $request->input('CNCDIRNOM_TRANSP');
            $query->whereHas('carrier', function ($q) use ($transporterName) {
                $q->where('CNCDIRNOM', 'like', '%' . $transporterName . '%');
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

        return view('freights', compact('freights'));
    }

    public function generatePDF(Request $request)
    {
        $query = Freight::query();

        // Filtros de búsqueda por proveedor
        if ($request->filled('CNCDIRNOM')) {
            $providerName = $request->input('CNCDIRNOM');
            $query->whereHas('provider', function ($q) use ($providerName) {
                $q->where('CNCDIRNOM', 'like', '%' . $providerName . '%');
            });
        }

        // Filtros de búsqueda por transportista
        if ($request->filled('CNCDIRNOM_TRANSP')) {
            $transporterName = $request->input('CNCDIRNOM_TRANSP');
            $query->whereHas('carrier', function ($q) use ($transporterName) {
                $q->where('CNCDIRNOM', 'like', '%' . $transporterName . '%');
            });
        }

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
