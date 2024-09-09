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
            return view('freights', compact('freights', 'filtersApplied'));
        }

        // Aplicar filtros
        if ($request->filled('CNCDIRNOM')) {
            $providerName = $request->input('CNCDIRNOM');
            $query->whereHas('provider', function ($q) use ($providerName) {
                $q->where('CNCDIRNOM', 'like', '%' . $providerName . '%');
            });
        }

        if ($request->filled('CNCDIRNOM_TRANSP')) {
            $transporterName = $request->input('CNCDIRNOM_TRANSP');
            $query->whereHas('carrier', function ($q) use ($transporterName) {
                $q->where('CNCDIRNOM', 'like', '%' . $transporterName . '%');
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $query->whereBetween('reception_date', [$startDate, $endDate]);
        }

        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $freights = $query->paginate(10)->appends($request->all());

        return view('freights', compact('freights', 'filtersApplied'));
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

        if ($request->filled('CNCDIRNOM_TRANSP')) {
            $transporterName = $request->input('CNCDIRNOM_TRANSP');
            $query->whereHas('carrier', function ($q) use ($transporterName) {
                $q->where('CNCDIRNOM', 'like', '%' . $transporterName . '%');
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } else {
            $startDate = Freight::min('reception_date');
            $endDate = Freight::max('reception_date');
        }

        $query->whereBetween('reception_date', [$startDate, $endDate]);

        $freights = $query->get();
        $groupedFreights = $freights->groupBy('supplier_name');

        $totalCost = $freights->sum('cost');
        $totalFreight = $freights->sum('freight');

        // Verificar si todas las fechas son iguales
        $uniqueDates = $freights->pluck('reception_date')->unique();
        if ($uniqueDates->count() === 1) {
            $singleDate = $uniqueDates->first();
        } else {
            $singleDate = null;
        }

        $pdf = PDF::loadView('report_freights', compact('groupedFreights', 'totalCost', 'totalFreight', 'startDate', 'endDate', 'singleDate'));

        return $pdf->stream('freights_report.pdf');
    }
}
