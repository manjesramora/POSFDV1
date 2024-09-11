<?php

namespace App\Http\Controllers;

// Importaciones necesarias para el funcionamiento del controlador

use Illuminate\Http\Request; // Permite manejar y acceder a los datos de la solicitud HTTP
use Illuminate\Support\Facades\Auth; // Facilita la autenticación de usuarios en la aplicación
use Picqer\Barcode\BarcodeGeneratorHTML; // Librería utilizada para generar códigos de barras en formato HTML
use Barryvdh\DomPDF\Facade\Pdf; // Librería para generar archivos PDF a partir de vistas
use Illuminate\Support\Facades\DB; // Proporciona acceso a la base de datos y permite realizar consultas SQL
use Illuminate\Support\Facades\Log; // Utilizada para registrar logs y errores en el sistema


class LabelcatalogController extends Controller
{
    // Constructor para establecer middleware y compartir Roles del Usuario Autenticado
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

    // Método principal para mostrar el catálogo de etiquetas
    public function labelscatalog(Request $request)
    {
        // Filtros de búsqueda basados en los inputs del request
        $productIdFilter = $request->input('productId');
        $skuFilter = $request->input('sku');
        $nameFilter = $request->input('name');
        $lineaFilter = $request->input('linea');
        $sublineaFilter = $request->input('sublinea');
        $departamentoFilter = $request->input('departamento');
        $activoFilter = $request->input('activo');
        $sortColumn = $request->input('sort', 'INPROD.INPRODID'); // Columna para ordenar por ID PRODUCTO en dado caso se cargue el catalogo en primera instancia ( Actualmente se omitio la carga de datos al iniciar la pagina )
        $sortDirection = $request->input('direction', 'asc');     // Dirección de la ordenación

        // Obtener el usuario autenticado
        $user = Auth::user();
        // Obtener los centros de costos asociados al usuario
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

        // Construcción de la consulta principal
        $query = DB::table('INSDOS')
            ->join('INPROD', 'INSDOS.INPRODID', '=', 'INPROD.INPRODID')
            ->leftJoin('INALPR', function ($join) {
                // Join con inventarios para obtener el stock
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
            // Join para obtener precios generales si no hay precios específicos de centros de costo
            ->leftJoin('AVPREC as general_price', function ($join) {
                $join->on('INPROD.INPRODID', '=', 'general_price.AVPRECPRO')
                    ->on('latest_general_prices.max_fecha', '=', 'general_price.AVPREFIPB')
                    ->where(function ($query) {
                        $query->whereNull('general_price.AVPRECALM')
                            ->orWhere('general_price.AVPRECALM', '');
                    });
            })
            // Join para obtener precios por centro de costos
            ->leftJoin('AVPREC as cc_price', function ($join) {
                $join->on('INPROD.INPRODID', '=', 'cc_price.AVPRECPRO')
                    ->on('latest_cc_prices.max_fecha', '=', 'cc_price.AVPREFIPB')
                    ->whereColumn('INSDOS.INALMNID', 'cc_price.AVPRECALM');
            })
            ->select(
                'INPROD.INPRODID',  // ID PRODUCTO
                'INPROD.INPRODDSC', // Descripción del producto
                'INPROD.INPRODI2',  // Código del SKU
                'INPROD.INPR02ID',  // Departamento
                'INPROD.INPR03ID',  // Línea
                'INPROD.INPR04ID',  // Sublínea
                'INPROD.INUMBAID',  // UM Base ( Unidad de Medida Base )
                'INPROD.INPRODCBR', // Código de barras del producto
                DB::raw('ROUND(INSDOS.INSDOSQDS, 2) as Existencia'),  // Existencias en inventario (En UM Base)
                'INSDOS.INALMNID as CentroCostos',  // Centro de costos
                'INALPR.INAPR17ID as TipoStock',    // Tipo de stock

                // Precio base tomando el del centro de costos si existe, o el global
                DB::raw('COALESCE(cc_price.AVPRECBAS, general_price.AVPRECBAS) as PrecioBase'),
                // Almacén del precio (general o específico por centro de costos)
                DB::raw('COALESCE(cc_price.AVPRECALM, CASE WHEN general_price.AVPRECALM IS NULL OR general_price.AVPRECALM = \'\' THEN \'Global\' ELSE general_price.AVPRECALM END) as AlmacenPrecio')
            )
            // Filtrar productos válidos
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

        // Aplicar filtros si están presentes en el request
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

        // Ejecutar la consulta solo si hay filtros aplicados
        if (
            !empty($productIdFilter) || !empty($skuFilter) || !empty($nameFilter) ||
            !empty($lineaFilter) || !empty($sublineaFilter) || !empty($departamentoFilter)
        ) {
            // Ordenar resultados
            $query->orderBy($sortColumn, $sortDirection);
            $labels = $query->paginate(20)->appends($request->query());
        } else {
            // Si no hay filtros, retornar una colección vacía
            $labels = collect([]);
        }
            // Devolver vista con resultados
        return view('label_catalog', compact('labels'));
    }






    // Método para imprimir etiquetas sin precio
    public function printLabel(Request $request)
    {
        $sku = $request->input('sku');
        $description = $request->input('description');
        $quantity = $request->input('quantity', 1);

        // Generar código de barras en HTML
        $generator = new BarcodeGeneratorHTML();
        $barcodeHtml = $generator->getBarcode($sku, $generator::TYPE_CODE_128);

        // Armar los datos para cada etiqueta
        $data = [
            'sku' => $sku,
            'description' => $description,
            'barcode' => $barcodeHtml
        ];
        // Crear una cantidad de etiquetas igual a la cantidad solicitada
        $labels = array_fill(0, $quantity, $data);

        // Generar PDF con las etiquetas
        $pdf = Pdf::loadView('label', ['labels' => $labels]);

        // Guardar el archivo PDF generado
        $pdfOutput = $pdf->output();
        $filename = 'labels.pdf';
        file_put_contents(public_path($filename), $pdfOutput);

        // Retornar la URL del archivo PDF generado
        return response()->json(['url' => asset($filename)]);
    }




     // Método para imprimir etiquetas con precios
    public function printLabelWithPrice(Request $request)
    {
        try {
            // Log de los datos recibidos
            Log::info('Datos recibidos en printLabelWithPrice:', $request->all());

            // Obtener los datos del request
            $sku = $request->input('sku');
            $description = $request->input('description');
            $quantity = $request->input('quantity', 1);
            $precioBase = $request->input('precioBase');
            $umv = $request->input('umv');
            $productId = $request->input('productId');

            // Validar los datos recibidos
            if (empty($sku) || empty($description)) {
                throw new \Exception('Datos incompletos');
            }

            if (is_null($precioBase) || $precioBase === '') {
                $precioBase = '0.00';
            }

            if (!is_numeric($precioBase)) {
                throw new \Exception('Precio base no es un número válido');
            }

            // Consultar si el producto lleva IVA
            $impuesto = DB::table('INPROD')
                ->where('INPRODID', $productId)
                ->value('INPRODMIV');

            // Convertir precio base a float para precisión
            $precioBaseFloat = (float)$precioBase;
            $precioAjustado = $precioBaseFloat;

            // Convertir el precio si se ha seleccionado una UMV diferente
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

            // Generar código de barras en HTML
            $generator = new BarcodeGeneratorHTML();
            $barcodeHtml = $generator->getBarcode($sku, $generator::TYPE_CODE_128);

            // Armar los datos de las etiquetas
            $data = [
                'sku' => $sku,
                'description' => $description,
                'barcode' => $barcodeHtml,
                'precioBase' => $precioBaseFormatted,
                'precioAjustado' => $precioAjustadoFormatted
            ];

            $labels = array_fill(0, $quantity, $data);

            // Generar PDF con las etiquetas
            $pdf = Pdf::loadView('label_with_price', ['labels' => $labels]);

            // Guardar el archivo PDF generado
            $pdfOutput = $pdf->output();
            $filename = 'labels_with_price.pdf';
            file_put_contents(public_path($filename), $pdfOutput);

            // Retornar la URL del archivo PDF generado
            return response()->json(['url' => asset($filename)]);
        } catch (\Exception $e) {

            // Log de errores
            Log::error('Error generando el PDF: ' . $e->getMessage());
            return response()->json(['error' => 'Error generando el PDF: ' . $e->getMessage()], 500);
        }
    }






    // Método para obtener la unidad de medida base y otros datos relacionados al producto
    public function getUmv($productId)
    {
        // Obtener unidad de medida base del producto
        $umBase = DB::table('INPROD')
            ->where('INPRODID', $productId)
            ->value('INUMBAID');

        // Obtener lista de unidades de medida válidas
        $umvList = DB::table('INFCCN')
            ->where('INPRODID', $productId)
            ->where('INUMINID', $umBase)
            ->pluck('INUMFNID')
            ->toArray();

        // Obtener factores de conversión por unidad de medida
        $umvFactors = DB::table('INFCCN')
            ->where('INPRODID', $productId)
            ->where('INUMINID', $umBase)
            ->pluck('INFCCNQTF', 'INUMFNID')
            ->toArray();

        // Obtener si el producto lleva IVA
        $impuesto = DB::table('INPROD')
            ->where('INPRODID', $productId)
            ->value('INPRODMIV');

        // Devolver los datos obtenidos en formato JSON
        return response()->json([
            'umBase' => $umBase,
            'umvList' => $umvList,
            'umvFactors' => $umvFactors,
            'impuesto' => $impuesto,
        ]);
    }


    // Método para convertir precios entre unidades de medida
    public function convertPrice(Request $request)
    {
        $sku = $request->input('sku');
        $umv = $request->input('umv');
        $precioBase = $request->input('precioBase');

        // Obtener la unidad de medida base del producto
        $umBase = DB::table('INPROD')
            ->where('INPRODID', $sku)
            ->value('INUMBAID');

        // Si la unidad seleccionada es la misma que la base, devolver el precio sin cambios
        if ($umv === $umBase || empty($umv)) {
            return response()->json(['precioAjustado' => $precioBase]);
        }

        // Obtener el factor de conversión entre la unidad base y la UMV seleccionada
        $conversionFactor = DB::table('INFCCN')
            ->where('INPRODID', $sku)
            ->where('INUMINID', $umBase)
            ->where('INUMFNID', $umv)
            ->value('INFCCNQTF');

        // Calcular el precio ajustado aplicando el factor de conversión
        $precioAjustado = $precioBase * $conversionFactor;

        // Retornar el precio ajustado en formato JSON
        return response()->json(['precioAjustado' => number_format($precioAjustado, 2, '.', '')]);
    }
}
