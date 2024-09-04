<?php

namespace App\Http\Controllers;

use App\Models\Insdos;
use App\Models\LabelCatalog;
use App\Models\AVPREC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabelcatalogController extends Controller
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

    public function labelscatalog(Request $request)
    {
        $productIdFilter = $request->input('productId');
        $skuFilter = $request->input('sku');
        $nameFilter = $request->input('name');
        $lineaFilter = $request->input('linea');
        $sublineaFilter = $request->input('sublinea');
        $departamentoFilter = $request->input('departamento');
        $activoFilter = $request->input('activo');
        $sortColumn = $request->input('sort', 'INPROD.INPRODID');
        $sortDirection = $request->input('direction', 'asc');
    
        $user = Auth::user();
        $centrosCostosIds = $user->costCenters->pluck('cost_center_id');
    
        // Subconsulta para obtener el precio base más reciente para cada producto donde ALMACEN es NULL o vacío
        $subQueryGeneral = DB::table('AVPREC')
            ->select('AVPRECPRO', DB::raw('MAX(AVPREFIPB) as max_fecha'))
            ->whereNull('AVPRECALM')
            ->orWhere('AVPRECALM', '')
            ->groupBy('AVPRECPRO');
    
        // Subconsulta para obtener el precio base más reciente para cada producto y centro de costos específico
        $subQueryCentroCosto = DB::table('AVPREC')
            ->select('AVPRECPRO', 'AVPRECALM', DB::raw('MAX(AVPREFIPB) as max_fecha'))
            ->whereIn('AVPRECALM', $centrosCostosIds)
            ->groupBy('AVPRECPRO', 'AVPRECALM');
    
        $query = DB::table('INSDOS')
            ->join('INPROD', 'INSDOS.INPRODID', '=', 'INPROD.INPRODID')
            ->leftJoin('INALPR', function ($join) {
                $join->on('INSDOS.INPRODID', '=', 'INALPR.INPRODID')
                    ->on('INSDOS.INALMNID', '=', 'INALPR.INALMNID');
            })
            // Join con subconsulta de precios generales
            ->leftJoinSub($subQueryGeneral, 'latest_general_prices', function ($join) {
                $join->on('INPROD.INPRODID', '=', 'latest_general_prices.AVPRECPRO');
            })
            // Join con subconsulta de precios por centro de costo
            ->leftJoinSub($subQueryCentroCosto, 'latest_cc_prices', function ($join) {
                $join->on('INPROD.INPRODID', '=', 'latest_cc_prices.AVPRECPRO')
                    ->on('INSDOS.INALMNID', '=', 'latest_cc_prices.AVPRECALM');
            })
            // Join final para traer los precios correctos
            ->leftJoin('AVPREC as general_price', function ($join) {
                $join->on('INPROD.INPRODID', '=', 'general_price.AVPRECPRO')
                    ->on('latest_general_prices.max_fecha', '=', 'general_price.AVPREFIPB')
                    ->where(function ($query) {
                        $query->whereNull('general_price.AVPRECALM')
                            ->orWhere('general_price.AVPRECALM', '');
                    });
            })
            ->leftJoin('AVPREC as cc_price', function ($join) {
                $join->on('INPROD.INPRODID', '=', 'cc_price.AVPRECPRO')
                    ->on('latest_cc_prices.max_fecha', '=', 'cc_price.AVPREFIPB')
                    ->whereColumn('INSDOS.INALMNID', 'cc_price.AVPRECALM');
            })
            ->select(
                'INPROD.INPRODID',
                'INPROD.INPRODDSC',
                'INPROD.INPRODI2',
                'INPROD.INPR02ID',
                'INPROD.INPR03ID',
                'INPROD.INPR04ID',
                'INPROD.INUMBAID',
                'INPROD.INPRODCBR',
                DB::raw('ROUND(INSDOS.INSDOSQDS, 2) as Existencia'),
                'INSDOS.INALMNID as CentroCostos',
                'INALPR.INAPR17ID as TipoStock',
                // Seleccionar el precio del centro de costos si existe, sino el general
                DB::raw('COALESCE(cc_price.AVPRECBAS, general_price.AVPRECBAS) as PrecioBase'),
                DB::raw('COALESCE(cc_price.AVPRECALM, CASE WHEN general_price.AVPRECALM IS NULL OR general_price.AVPRECALM = \'\' THEN \'Global\' ELSE general_price.AVPRECALM END) as AlmacenPrecio')
            )
            ->whereNotNull('INPROD.INPRODDSC')
            ->where('INPROD.INPRODDSC', '<>', '')
            ->where('INPROD.INPRODDSC', '<>', '.')
            ->where('INPROD.INPRODDSC', '<>', '*')
            ->where('INPROD.INPRODDSC', '<>', '..')
            ->where('INPROD.INPRODDSC', '<>', '...')
            ->where('INPROD.INPRODDSC', '<>', '....')
            ->whereNotNull('INALPR.INAPR17ID')
            ->where('INALPR.INAPR17ID', '<>', '')
            ->where('INALPR.INAPR17ID', '<>', '-1')
            ->whereNotIn('INPROD.INTPALID', ['O', 'D'])
            ->whereRaw('ISNUMERIC(INPROD.INTPALID) = 0')
            ->whereRaw('LEN(INPROD.INPRODI2) >= 7')
            ->whereIn('INSDOS.INALMNID', $centrosCostosIds);
    
        // Aplicar filtros
        if (!empty($productIdFilter)) {
            $query->where('INPROD.INPRODID', 'like', $productIdFilter . '%');
        }
        if (!empty($skuFilter)) {
            $query->where('INPROD.INPRODI2', 'like', $skuFilter . '%');
        }
        if (!empty($nameFilter)) {
            $query->where('INPROD.INPRODDSC', 'like', $nameFilter . '%');
        }
        if (!empty($lineaFilter) && $lineaFilter !== 'LN') {
            $query->where('INPR03ID', 'like', $lineaFilter . '%');
        }
        if (!empty($sublineaFilter) && $sublineaFilter !== 'SB') {
            $query->where('INPR04ID', 'like', $sublineaFilter . '%');
        }
        if (!empty($departamentoFilter)) {
            $query->where('INPROD.INPR02ID', 'like', $departamentoFilter . '%');
        }
        if ($activoFilter === 'activos') {
            $query->where('INALPR.INAPR17ID', '<>', 'X');
        }
    
        // Ejecutar la consulta solo si hay filtros activos
        if (!empty($productIdFilter) || !empty($skuFilter) || !empty($nameFilter) || 
            !empty($lineaFilter) || !empty($sublineaFilter) || !empty($departamentoFilter) || 
            !empty($activoFilter)) {
            
            $query->orderBy($sortColumn, $sortDirection);
            $labels = $query->paginate(20)->appends($request->query());
        } else {
            // Retornar una colección vacía si no hay filtros activos
            $labels = collect([]);
        }
    
        return view('etiquetascatalogo', compact('labels'));
    }
    






    public function printLabel(Request $request)
    {
        $sku = $request->input('sku');
        $description = $request->input('description');
        $quantity = $request->input('quantity', 1);

        $generator = new BarcodeGeneratorHTML();
        $barcodeHtml = $generator->getBarcode($sku, $generator::TYPE_CODE_128);

        $data = [
            'sku' => $sku,
            'description' => $description,
            'barcode' => $barcodeHtml
        ];

        $labels = array_fill(0, $quantity, $data);

        $pdf = Pdf::loadView('label', ['labels' => $labels]);

        $pdfOutput = $pdf->output();
        $filename = 'labels.pdf';
        file_put_contents(public_path($filename), $pdfOutput);

        return response()->json(['url' => asset($filename)]);
    }

    public function printLabelWithPrice(Request $request)
    {
        try {
            Log::info('Datos recibidos en printLabelWithPrice:', $request->all());

            $sku = $request->input('sku');
            $description = $request->input('description');
            $quantity = $request->input('quantity', 1);
            $precioBase = $request->input('precioBase');
            $umv = $request->input('umv');
            $productId = $request->input('productId');

            if (empty($sku) || empty($description)) {
                throw new \Exception('Datos incompletos');
            }

            if (is_null($precioBase) || $precioBase === '') {
                $precioBase = '0.00';
            }

            if (!is_numeric($precioBase)) {
                throw new \Exception('Precio base no es un número válido');
            }

            // Obtener si el producto lleva IVA
            $impuesto = DB::table('INPROD')
                ->where('INPRODID', $productId)
                ->value('INPRODMIV');

            // Convertir precio base a float para precisión
            $precioBaseFloat = (float)$precioBase;
            $precioAjustado = $precioBaseFloat;

            // Si se selecciona una UMV diferente a la UMB, realizar la conversión
            if (!empty($umv)) {
                $conversionFactor = DB::table('INFCCN')
                    ->where('INPRODID', $productId)
                    ->where('INUMINID', function ($query) use ($productId) {
                        $query->select('INUMBAID')
                            ->from('INPROD')
                            ->where('INPRODID', $productId);
                    })
                    ->where('INUMFNID', $umv)
                    ->value('INFCCNQTF');

                if ($conversionFactor) {
                    $precioAjustado = $precioBaseFloat * $conversionFactor;
                }
            }

            // Calcular el precio con IVA si aplica
            if ($impuesto == 1) {
                $iva = 0.16; // IVA 16%
                $precioBaseConIVA = $precioBaseFloat * (1 + $iva);
                $precioAjustadoConIVA = $precioAjustado * (1 + $iva);
            } else {
                $precioBaseConIVA = $precioBaseFloat;
                $precioAjustadoConIVA = $precioAjustado;
            }

            // Formatear los precios a dos decimales solo para visualización, sin redondear
            $precioBaseFormatted = sprintf('%.2f', floor($precioBaseConIVA * 100) / 100);
            $precioAjustadoFormatted = sprintf('%.2f', floor($precioAjustadoConIVA * 100) / 100);

            $generator = new BarcodeGeneratorHTML();
            $barcodeHtml = $generator->getBarcode($sku, $generator::TYPE_CODE_128);

            $data = [
                'sku' => $sku,
                'description' => $description,
                'barcode' => $barcodeHtml,
                'precioBase' => $precioBaseFormatted,
                'precioAjustado' => $precioAjustadoFormatted
            ];

            $labels = array_fill(0, $quantity, $data);

            $pdf = Pdf::loadView('label_with_price', ['labels' => $labels]);

            $pdfOutput = $pdf->output();
            $filename = 'labels_with_price.pdf';
            file_put_contents(public_path($filename), $pdfOutput);

            return response()->json(['url' => asset($filename)]);
        } catch (\Exception $e) {
            Log::error('Error generando el PDF: ' . $e->getMessage());
            return response()->json(['error' => 'Error generando el PDF: ' . $e->getMessage()], 500);
        }
    }







    public function getUmv($productId)
    {
        $umBase = DB::table('INPROD')
            ->where('INPRODID', $productId)
            ->value('INUMBAID');

        $umvList = DB::table('INFCCN')
            ->where('INPRODID', $productId)
            ->where('INUMINID', $umBase)
            ->pluck('INUMFNID')
            ->toArray();

        $umvFactors = DB::table('INFCCN')
            ->where('INPRODID', $productId)
            ->where('INUMINID', $umBase)
            ->pluck('INFCCNQTF', 'INUMFNID')
            ->toArray();

        $impuesto = DB::table('INPROD')
            ->where('INPRODID', $productId)
            ->value('INPRODMIV');

        return response()->json([
            'umBase' => $umBase,
            'umvList' => $umvList,
            'umvFactors' => $umvFactors,
            'impuesto' => $impuesto,
        ]);
    }



    public function convertPrice(Request $request)
    {
        $sku = $request->input('sku');
        $umv = $request->input('umv');
        $precioBase = $request->input('precioBase');

        // Obtener la unidad de medida base del producto
        $umBase = DB::table('INPROD')
            ->where('INPRODID', $sku)
            ->value('INUMBAID');

        // Si la UMV es la misma que la UMB, no hay conversión
        if ($umv === $umBase || empty($umv)) {
            return response()->json(['precioAjustado' => $precioBase]);
        }

        // Obtener el factor de conversión
        $conversionFactor = DB::table('INFCCN')
            ->where('INPRODID', $sku)
            ->where('INUMINID', $umBase)
            ->where('INUMFNID', $umv)
            ->value('INFCCNQTF');

        // Calcular el precio ajustado
        $precioAjustado = $precioBase * $conversionFactor;

        return response()->json(['precioAjustado' => number_format($precioAjustado, 2, '.', '')]);
    }
}
