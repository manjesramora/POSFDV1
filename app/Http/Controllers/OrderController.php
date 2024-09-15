<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Providers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

class OrderController extends Controller
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

    public function autocompleteProviders(Request $request)
    {
        $query = $request->input('query');
        $field = $request->input('field');
        $screen = $request->input('screen'); // Especificar pantalla

        $providersQuery = Providers::query();

        if ($screen == 'orders') {
            // Excluir proveedores cuyo CNCDIRID comience con '4'
            $providersQuery->where('CNCDIRID', 'like', '3%');
        }

        // Aplicar filtros de búsqueda por CNCDIRID o CNCDIRNOM
        $providers = $providersQuery
            ->when($field == 'CNCDIRID', function ($q) use ($query) {
                return $q->where('CNCDIRID', 'like', '%' . $query . '%');
            })
            ->when($field == 'CNCDIRNOM', function ($q) use ($query) {
                return $q->where('CNCDIRNOM', 'like', '%' . $query . '%');
            })
            ->limit(10) // Limitar el número de resultados
            ->get(['CNCDIRID', 'CNCDIRNOM']);

        return response()->json($providers);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Redirigir al login si no hay usuario autenticado
        if (!$user) {
            return redirect()->route('login');
        }

        // Obtener los centros de costos asociados al usuario
        $centrosCostosIds = $user->costCenters->pluck('cost_center_id')->toArray();
        $query = Order::query();

        // Filtrar por los centros de costos del usuario
        if (!empty($centrosCostosIds)) {
            $query->whereIn('ACMVOIALID', $centrosCostosIds);
        }

        // Validación de fechas de entrada
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Definir la fecha límite de 6 meses atrás
        $sixMonthsAgo = Carbon::now()->subMonths(6)->format('Y-m-d');

        // Aplicar filtros de fechas si se proporcionan
        if ($startDate && $endDate) {
            try {
                $start = Carbon::parse($startDate)->format('Y-m-d');
                $end = Carbon::parse($endDate)->endOfDay()->format('Y-m-d');
            } catch (\Exception $e) {
                return redirect()->route('orders')->with('error', 'Formato de fecha no válido.');
            }

            // Asegurar que las fechas no son mayores a 6 meses desde la fecha actual
            if (Carbon::parse($start)->lt($sixMonthsAgo) || Carbon::parse($end)->lt($sixMonthsAgo)) {
                return redirect()->route('orders')->with('error', 'El rango de fechas no puede ser mayor a 6 meses desde la fecha actual.');
            }

            // Filtrar fechas con BETWEEN sin usar TRY_CONVERT
            $query->whereBetween(DB::raw("CAST(ACMVOIFDOC AS DATE)"), [$start, $end]);
        } else {
            // Si no se especifican fechas, usar los últimos 6 meses
            $query->where(DB::raw("CAST(ACMVOIFDOC AS DATE)"), '>=', $sixMonthsAgo);
        }

        // Definir valores predeterminados para el ordenamiento
        $sortColumn = $request->input('sortColumn', 'ACMVOIDOC');
        $sortDirection = $request->input('sortDirection', 'desc');

        // Lista de columnas permitidas para ordenar
        $sortableColumns = ['CNTDOCID', 'ACMVOIDOC', 'CNCDIRID', 'CNCDIRNOM', 'ACMVOIFDOC', 'ACMVOIALID'];

        if (in_array($sortColumn, $sortableColumns)) {
            if ($sortColumn == 'CNCDIRNOM') {
                // Si se ordena por nombre de proveedor, hacer join con la tabla CNCDIR
                $query->join('CNCDIR', 'ACMVOR.CNCDIRID', '=', 'CNCDIR.CNCDIRID')
                    ->orderBy('CNCDIR.CNCDIRNOM', $sortDirection);
            } else {
                // Ordenar por la columna seleccionada
                $query->orderBy($sortColumn, $sortDirection);
            }
        } else {
            // Ordenar por defecto por ACMVOIDOC descendente
            $query->orderBy('ACMVOIDOC', 'desc');
        }

        // Aplicar otros filtros si están presentes
        if ($request->filled('ACMVOIDOC')) {
            $query->where('ACMVOIDOC', $request->input('ACMVOIDOC'));
        }

        if ($request->filled('CNCDIRID')) {
            $query->where('ACMVOR.CNCDIRID', $request->input('CNCDIRID'));
        }

        if ($request->filled('CNCDIRNOM')) {
            // Filtro por nombre del proveedor
            $query->whereHas('provider', function ($q) use ($request) {
                $q->where('CNCDIR.CNCDIRNOM', 'like', '%' . $request->input('CNCDIRNOM') . '%');
            });
        }

        // Filtrar solo órdenes con movimientos pendientes de recepción
        $query->whereExists(function ($subquery) {
            $subquery->select(DB::raw(1))
                    ->from('ACMVOR1')
                    ->whereRaw('ACMVOR1.ACMVOIDOC = ACMVOR.ACMVOIDOC')
                    ->whereRaw('ACMVOR1.ACMVOIQTO > ACMVOR1.ACMVOIQTR');
        });

        // Paginación
        $orders = $query->paginate(30);

        // Si es una petición AJAX, renderizar solo la tabla de órdenes
        if ($request->ajax()) {
            return view('orders_table', compact('orders', 'sortColumn', 'sortDirection'))->render();
        }

        // Renderizar la vista completa de órdenes
        return view('orders', compact('orders', 'sortColumn', 'sortDirection', 'startDate', 'endDate'));
    }


    public function showReceptions($ACMVOIDOC)
    {
        if (!is_numeric($ACMVOIDOC)) {
            return redirect()->route('orders')->with('error', 'El número de orden no es válido.');
        }

        $order = Order::where('ACMVOIDOC', $ACMVOIDOC)
            ->with('provider')
            ->first();

        if (!$order) {
            return redirect()->route('orders')->with('error', 'Orden no encontrada.');
        }

        $receptions = DB::table('ACMVOR1')
            ->where('ACMVOIDOC', $ACMVOIDOC)
            ->whereRaw('ACMVOIQTO > ACMVOIQTR') // Filtrar partidas no completamente recepcionadas
            ->select('ACMVOILIN', 'ACMVOIPRID', 'ACMVOIPRDS', 'ACMVOINPAR', 'ACMVOIUMT', 'ACMVOIQTO', 'ACMVOINPO', 'ACMVOIIVA', 'ACMVOIQTP')
            ->get();

        if ($receptions->isEmpty()) {
            return redirect()->route('orders')->with('error', 'No hay partidas válidas para esta recepción.');
        }

        $provider = Providers::where('CNCDIRID', $order->CNCDIRID)->first();

        $cntdoc = DB::table('cntdoc')
            ->where('cntdocid', 'RCN')
            ->first();

        if ($cntdoc && isset($cntdoc->CNTDOCNSIG)) {
            $num_rcn_letras = $cntdoc->CNTDOCNSIG;
            $new_value = is_numeric($num_rcn_letras) ? intval($num_rcn_letras) + 1 : chr(ord($num_rcn_letras) + 1);

            DB::table('cntdoc')
                ->where('cntdocid', 'RCN')
                ->update(['CNTDOCNSIG' => $new_value]);
        } else {
            $num_rcn_letras = 'NUMERO'; // Ajusta esto según tu lógica
        }

        $currentDate = now()->toDateString();

        return view('receptions', compact('receptions', 'order', 'provider', 'num_rcn_letras', 'currentDate'));
    }


    public function insertFreight($validatedData, $provider)
    {
        try {
            // Verificar si todos los campos requeridos están presentes
            if (
                !isset($validatedData['document_type']) ||
                !isset($validatedData['document_number']) ||
                !isset($validatedData['document_type1']) ||
                !isset($validatedData['document_number1']) ||
                !isset($validatedData['freight']) ||
                !isset($validatedData['total_cost']) ||
                !isset($validatedData['supplier_name']) ||
                !isset($validatedData['reference_type']) ||
                !isset($validatedData['store']) ||
                !isset($validatedData['reference']) ||
                !isset($validatedData['reception_date'])
            ) {
                Log::error("Datos faltantes para insertar flete.", $validatedData);
                return false;
            }

            // Inserción en la tabla Freights
            DB::table('Freights')->insert([
                'document_type' => $validatedData['document_type'],
                'document_number' => $validatedData['document_number'],
                'document_type1' => $validatedData['document_type1'],
                'document_number1' => $validatedData['document_number1'],
                'cost' => $validatedData['total_cost'],
                'freight' => $validatedData['freight'],
                'supplier_number' => $provider->CNCDIRID,
                'carrier_number' => $validatedData['carrier_number'],
                'carrier_name' => $validatedData['carrier_name'],
                'supplier_name' => $validatedData['supplier_name'],
                'reference_type' => $validatedData['reference_type'],
                'store' => $validatedData['store'],
                'reference' => $validatedData['reference'],
                'reception_date' => $validatedData['reception_date'],
                'freight_percentage' => 0.0,
            ]);

            Log::info("Datos de flete insertados correctamente en la tabla Freights.");
            return true;
        } catch (\Exception $e) {
            Log::error("Error al insertar freight: " . $e->getMessage());
            return false;
        }
    }
    public function getNewCNTDOCNSIG($docType)
    {
        // Verifica si el documento es 'PC'. Si es así, no hagas nada
        if ($docType === 'PC') {
            return 'NUMERO'; // O cualquier valor por defecto que quieras para PC
        }

        // Si es otro tipo de documento como 'RCN', continúa con la lógica normal
        $cntdoc = DB::table('cntdoc')
            ->where('cntdocid', $docType) // Utiliza el tipo de documento dinámicamente
            ->first();

        if ($cntdoc && isset($cntdoc->CNTDOCNSIG)) {
            $num_rcn_letras = $cntdoc->CNTDOCNSIG;

            // Incrementa solo si no es 'PC'
            if (is_numeric($num_rcn_letras)) {
                $new_value = intval($num_rcn_letras) + 1;
            } else {
                $new_value = chr(ord($num_rcn_letras) + 1);
            }

            DB::table('cntdoc')
                ->where('cntdocid', $docType) // Actualiza solo si no es 'PC'
                ->update(['CNTDOCNSIG' => $new_value]);

            return $new_value;
        } else {
            return 'NUMERO'; // Valor por defecto si no se encuentra el documento
        }
    }

    public function insertPartidas($validatedData, $cantidadesRecibidas, $preciosUnitarios, $order, $provider)
    {
        $fechaActual = now();
        $horaActual = now()->format('H:i:s');
        $usuario = Auth::user()->name ?? 'Sistema';

        try {
            foreach ($cantidadesRecibidas as $index => $cantidadRecibida) {
                if ($cantidadRecibida > 0) {
                    if (
                        !isset($validatedData['acmvoilin'][$index]) ||
                        !isset($validatedData['acmvoiprid'][$index]) ||
                        !isset($validatedData['acmvoiprds'][$index]) ||
                        !isset($validatedData['acmvoiumt'][$index]) ||
                        !isset($validatedData['acmvoiiva'][$index])
                    ) {
                        Log::error("Datos de partida no encontrados para el índice {$index}");
                        throw new \Exception("Datos de partida faltantes para el índice {$index}. Abortando operación.");
                    }

                    $acmvoilin = $validatedData['acmvoilin'][$index];
                    $acmvoiprid = $validatedData['acmvoiprid'][$index];
                    $acmvoiprds = $validatedData['acmvoiprds'][$index];
                    $acmvoiumt = $validatedData['acmvoiumt'][$index];
                    $acmvoiiva = $validatedData['acmvoiiva'][$index];

                    Log::info("Procesando partida", ['index' => $index, 'ACMVOILIN' => $acmvoilin, 'INPRODID' => $acmvoiprid, 'UMT' => $acmvoiumt, 'IVA' => $acmvoiiva]);

                    $costoUnitario = isset($preciosUnitarios[$index]) && $preciosUnitarios[$index] > 0 ? (float) $preciosUnitarios[$index] : null;

                    if ($costoUnitario === null) {
                        Log::error("Costo unitario no ingresado o inválido para la partida con ID {$acmvoiprid}, se omite la recepción.");
                        continue;
                    }

                    $costoTotal = number_format((float) round($cantidadRecibida * $costoUnitario, 4), 4, '.', '');

                    $inserted = $this->insertOrUpdateIncrdx($validatedData, $acmvoilin, $acmvoiprid, $cantidadRecibida, $costoTotal, $costoUnitario, $provider, $order, $acmvoiumt);

                    if (!$inserted) {
                        throw new \Exception("Error al insertar o actualizar en incrdx para la partida con ID {$acmvoiprid}");
                    }

                    $this->updateInsdos($validatedData['store'], $acmvoiprid, $cantidadRecibida, $costoTotal);

                    $this->insertAcmroi($validatedData, [
                        'ACMVOILIN' => $acmvoilin,
                        'ACMVOIPRID' => $acmvoiprid,
                        'ACMVOIPRDS' => $acmvoiprds,
                        'ACMVOIUMT' => $acmvoiumt,
                        'ACMVOIIVA' => $acmvoiiva
                    ], $cantidadRecibida, $costoTotal, $costoUnitario, $order, $provider, $usuario, $fechaActual, $horaActual);
                } else {
                    Log::warning("Cantidad recibida es 0 o menor para la partida con índice {$index}");
                }
            }
            Log::info('Partidas procesadas con éxito.');
            return true;
        } catch (\Exception $e) {
            Log::error("Error al procesar la recepción de la orden: " . $e->getMessage());
            throw $e;
        }
    }

    public function insertOrUpdateIncrdx($validatedData, $acmvoilin, $acmvoiprid, $cantidadRecibida, $costoTotal, $costoUnitario, $provider, $order, $unidadMedida)
    {
        $user = Auth::user();
        $centroCosto = trim($user->costCenters->first()->cost_center_id); // Limpiar espacios en blanco

        $connectionLocal = 'FD04'; // La conexión local siempre será FD04
        $connectionRemota = $centroCosto;

        try {
            // Validación adicional para evitar procesar datos incompletos
            if (empty($cantidadRecibida) || !is_numeric($cantidadRecibida) || $cantidadRecibida <= 0) {
                Log::warning("Cantidad recibida inválida o vacía para el producto con ID {$acmvoiprid}, no se procesará.");
                return false;
            }

            if (empty($costoUnitario) || !is_numeric($costoUnitario) || $costoUnitario <= 0) {
                Log::warning("Costo unitario inválido o vacío para el producto con ID {$acmvoiprid}, no se procesará.");
                return false;
            }

            Log::info("Insertando/actualizando en incrdx en conexiones locales y remotas");

            // Listado de conexiones en las que se insertará/actualizará (local + remota si es FD09 o FD10)
            $connections = [$connectionLocal];
            if (in_array($connectionRemota, ['FD09', 'FD10'])) {
                $connections[] = $connectionRemota;
            }

            foreach ($connections as $connection) {
                Log::info("Insertando/actualizando en incrdx en la conexión: {$connection}");

                // Obtener el idioma de la base de datos
                $dbLanguage = DB::connection($connection)->select(DB::raw("SELECT @@language AS 'Idioma'"))[0]->Idioma;
                Log::info("Idioma detectado para la conexión {$connection}: {$dbLanguage}");

                // Definir el formato de la fecha según el idioma
                $fechaActual = now();
                $defaultDate = '1753-01-01 00:00:00.000'; // Valor por defecto
                if (strtolower($dbLanguage) === 'spanish') {
                    $fechaActualFormatted = $fechaActual->format('d/m/Y H:i:s');  // Formato para español
                } else {
                    $fechaActualFormatted = $fechaActual->format('Y-m-d H:i:s');  // Formato por defecto en inglés
                }
                Log::info("Fecha actual formateada para {$connection}: {$fechaActualFormatted}");

                try {
                    // Verificar si el registro ya existe en incrdx para la conexión actual
                    $existsIncrdx = DB::connection($connection)->table('incrdx')
                        ->where('INALMNID', $validatedData['store'])
                        ->where('INPRODID', (int) $acmvoiprid)
                        ->where('CNTDOCID', 'RCN')
                        ->where('INCRDXDOC', (int) $validatedData['document_number1'])
                        ->where('INCRDXLIN', $acmvoilin)
                        ->exists();

                    if ($existsIncrdx) {
                        Log::info("Registro encontrado en incrdx. Procediendo a actualizar.");
                        // Actualizar los valores si ya existe el registro
                        DB::connection($connection)->table('incrdx')
                            ->where('INALMNID', $validatedData['store'])
                            ->where('INPRODID', (int) $acmvoiprid)
                            ->where('CNTDOCID', 'RCN')
                            ->where('INCRDXDOC', (int) $validatedData['document_number1'])
                            ->where('INCRDXLIN', $acmvoilin)
                            ->update([
                                'INCRDXQTY' => DB::raw('INCRDXQTY + ' . (float) $cantidadRecibida),
                                'INCRDXVAL' => DB::raw('INCRDXVAL + ' . (float) $costoTotal),
                                'INCRDXVANT' => DB::raw('INCRDXVANT + ' . (float) $costoTotal),
                            ]);
                        Log::info("Registro actualizado correctamente en incrdx.");
                    } else {
                        Log::info("No se encontró el registro en incrdx. Procediendo a insertar un nuevo registro.");
                        // Insertar un nuevo registro si no existe
                        DB::connection($connection)->table('incrdx')->insert([
                            'INALMNID' => substr($validatedData['store'], 0, 15),
                            'INPRODID' => (int) $acmvoiprid,
                            'INLOTEID' => ' ',
                            'CNTDOCID' => 'RCN',
                            'INCRDXDOC' => (int) $validatedData['document_number1'],
                            'INCRDXLIN' => $acmvoilin,
                            'INCRDXMON' => 'MXP',
                            'INCRDXLIB' => 'NL',
                            'INCRDXFTRN' => DB::raw("CONVERT(DATETIME, '{$fechaActualFormatted}', 120)"), // Conversión de la fecha para SQL Server
                            'INCRDXQTY' => (float) $cantidadRecibida,
                            'INCRDXCU' => (float) $costoUnitario,
                            'INCRDXVAL' => (float) $costoTotal,
                            'INCRDXVANT' => (float) $costoTotal,
                            'INCRDXUMB' => substr((string) $unidadMedida, 0, 3),
                        ]);

                        Log::info("Nuevo registro insertado correctamente en incrdx.");
                    }
                } catch (\Exception $ex) {
                    Log::error("Error durante el proceso en incrdx para la conexión {$connection}: {$ex->getMessage()}");
                    throw $ex; // Asegúrate de atrapar cualquier error
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Error al insertar/actualizar en incrdx: " . $e->getMessage());
            throw $e;
        }
    }

    public function insertAcmroi($validatedData, $partida, $cantidadRecibida, $costoTotal, $costoUnitario, $order, $provider, $usuario, $fechaActual, $horaActual)
    {
        try {
            $acmroilin = isset($partida['ACMVOILIN']) ? (int) $partida['ACMVOILIN'] : 0;
            if ($acmroilin === 0) {
                throw new \Exception("El valor de ACMVOILIN es nulo o cero para la partida con ID {$partida['ACMVOIPRID']}");
            }

            // Definir las conexiones local y remota
            $connectionLocal = 'FD04';
            $connectionRemota = trim(Auth::user()->costCenters->first()->cost_center_id); // Eliminamos espacios en blanco

            // Obtener el valor ACMVOIFDO2 de la base de datos
            $ACMVOIFDO2 = DB::connection($connectionRemota)->table('acmvor1')
                ->where('ACMVOIDOC', $order->ACMVOIDOC)
                ->value('ACMVOIFDO2');

            // Validar y obtener ACMVOIFDOC del pedido
            $ACMVOIFDOC = $order->ACMVOIFDOC ?? null;

            // Obtener detalles del producto para calcular volumen y peso
            $producto = DB::table('inprod')
                ->where('INPRODID', (int) $partida['ACMVOIPRID'])
                ->first();

            if (!$producto) {
                throw new \Exception("Producto con ID {$partida['ACMVOIPRID']} no encontrado.");
            }

            // Cálculo de volumen y peso
            $acmroiVolu = number_format((float) ($producto->INPRODVOL * $cantidadRecibida), 6, '.', '');
            $acmroiPesou = number_format((float) ($producto->INPRODPESO * $cantidadRecibida), 6, '.', '');

            // Listado de conexiones a afectar (local + remota si es FD09 o FD10)
            $connections = [$connectionLocal];
            if (in_array($connectionRemota, ['FD09', 'FD10'])) {
                $connections[] = $connectionRemota;
            }

            // Detectar el idioma antes de formatear fechas
            foreach ($connections as $connection) {
                Log::info("Insertando en ACMROI en la conexión: {$connection}");

                $dbLanguage = DB::connection($connection)->select(DB::raw("SELECT @@language AS 'Idioma'"))[0]->Idioma;
                $reception_date = now();
                $ACMVOIFDOC_formatted = $ACMVOIFDOC;
                $ACMVOIFDO2_formatted = $ACMVOIFDO2;
                $defaultDate = '1753-01-01 00:00:00.000'; // Valor por defecto para campos de fecha

                // Ajustar el formato de la fecha actual y otras fechas según el idioma de la base de datos
                if (strtolower($dbLanguage) === 'english') {
                    $reception_date = $reception_date->format('Y-m-d H:i:s');
                    $ACMVOIFDOC_formatted = $ACMVOIFDOC ? Carbon::parse($ACMVOIFDOC)->format('Y-m-d H:i:s') : $defaultDate;
                    $ACMVOIFDO2_formatted = $ACMVOIFDO2 ? Carbon::parse($ACMVOIFDO2)->format('Y-m-d H:i:s') : $defaultDate;
                    $fechaActual_formatted = $fechaActual->format('Y-m-d H:i:s');
                } else {
                    $reception_date = $reception_date->format('Y-m-d H:i:s');
                    $ACMVOIFDOC_formatted = $ACMVOIFDOC ? Carbon::parse($ACMVOIFDOC)->format('Y-m-d H:i:s') : $defaultDate;
                    $ACMVOIFDO2_formatted = $ACMVOIFDO2 ? Carbon::parse($ACMVOIFDO2)->format('Y-m-d H:i:s') : $defaultDate;
                    $fechaActual_formatted = $fechaActual->format('Y-m-d H:i:s');
                }

                // Insertar datos en ACMROI con las fechas formateadas según el idioma
                $insertData = [
                    'CNCIASID' => 1,
                    'ACMROITDOC' => 'RCN',
                    'ACMROINDOC' => isset($validatedData['document_number1']) ? (int) $validatedData['document_number1'] : 0,
                    'CNTDOCID' => 'OL1',
                    'ACMROIDOC' => isset($order->ACMVOIDOC) ? (int) $order->ACMVOIDOC : 0,
                    'ACMROILIN' => $acmroilin,
                    'ACMROIFREC' => DB::raw("CONVERT(DATETIME, '{$reception_date}', 120)"),  // Fecha de recepción
                    'CNCDIRID' => isset($provider->CNCDIRID) ? (int) $provider->CNCDIRID : 0,
                    'INALMNID' => isset($validatedData['store']) ? substr($validatedData['store'], 0, 15) : '',
                    'ACMVOIAOD' => isset($order->ACMVOIAOD) ? substr($order->ACMVOIAOD, 0, 3) : '',
                    'CNCMNMID' => isset($order->CNCMNMID) ? substr($order->CNCMNMID, 0, 3) : '',
                    'ACMROIFDOC' => DB::raw("CONVERT(DATETIME, '{$ACMVOIFDOC_formatted}', 120)"),  // Fecha ACMVOIFDOC
                    'ACMROIUSRC' => '          ',
                    'ACMROIFCEP' => DB::raw("CONVERT(DATETIME, '{$reception_date}', 120)"),  // Fecha de recepción
                    'ACMROIFREQ' => DB::raw("CONVERT(DATETIME, '{$reception_date}', 120)"),  // Fecha de recepción
                    'ACMROIFTRN' => DB::raw("CONVERT(DATETIME, '{$reception_date}', 120)"),  // Fecha de transacción
                    'ACMROIFCNT' => DB::raw("CONVERT(DATETIME, '{$reception_date}', 120)"),  // Fecha de conteo
                    'ACMVOIPR' => $order->ACMVOIPR,
                    'INPRODID' => (int) $partida['ACMVOIPRID'],
                    'ACMROIDSC' => isset($partida['ACMVOIPRDS']) ? substr($partida['ACMVOIPRDS'], 0, 60) : '',
                    'ACMROIUMT' => isset($partida['ACMVOIUMT']) ? substr($partida['ACMVOIUMT'], 0, 3) : '',
                    'ACMROIIVA' => isset($partida['ACMVOIIVA']) ? (float) $partida['ACMVOIIVA'] : 0.0,
                    'ACMROIQT' => number_format((float) $cantidadRecibida, 4, '.', ''),
                    'ACMROIQTTR' => number_format((float) $cantidadRecibida, 6, '.', ''),
                    'ACMROINP' => $costoUnitario,
                    'ACMROINM' => $costoTotal * 1.16,
                    'ACMROINI' => $costoTotal * 0.16,
                    'ACMROING' => $costoTotal,
                    'ACMROINP2' => $costoUnitario,
                    'ACMROIDOC2' => isset($validatedData['document_number1']) ? (int) $validatedData['document_number1'] : 0,
                    'ACMROIDOI2' => 'RCN',
                    'ACMROITDOCCAN' => ' ',
                    'ACMROINDOCCAN' => 0,
                    'ACMROIDOI3' => '   ',
                    'ACMROIDOC3' => 0,
                    'ACMROIREF' => isset($validatedData['reference']) ? substr($validatedData['reference'], 0, 60) : '',
                    'ACMROITREF' => isset($validatedData['reference_type']) ? (int) $validatedData['reference_type'] : 0,
                    'ACMROIVOLU' => $acmroiVolu,  // Volumen del producto
                    'ACMROIPESOU' => $acmroiPesou,  // Peso del producto
                    'ACMROIFGPT' => DB::raw("CONVERT(DATETIME, '{$defaultDate}', 120)"), // Fecha por defecto
                    'ACMROIFENT' => DB::raw("CONVERT(DATETIME, '{$fechaActual_formatted}', 120)"),  // Fecha actual formateada
                    'ACMROIFSAL' => DB::raw("CONVERT(DATETIME, '{$fechaActual_formatted}', 120)"),  // Fecha actual formateada
                    'ACMROIFECC' => DB::raw("CONVERT(DATETIME, '{$defaultDate}', 120)"), // Fecha por defecto
                    'ACMROIACCT' => 1,
                    'ACMROIFOC' => DB::raw("CONVERT(DATETIME, '{$defaultDate}', 120)"),  // Fecha por defecto
                    'ACMROICXP' => 'N',
                    'ACMROIFDC' => DB::raw("CONVERT(DATETIME, '{$reception_date}', 120)"),  // Fecha formateada
                    'ACMROIFVN' => DB::raw("CONVERT(DATETIME, '{$defaultDate}', 120)"),  // Fecha por defecto
                    'ACMROING2' => $costoTotal,
                    'ACMROINI2' => $costoTotal * 0.16,
                    'ACMROINM2' => $costoTotal * 1.16,
                    'ACMROIVOLT' => 0,
                    'ACMROIPESOT' => 0,
                    'ACMROINPED' => 0,
                    'ACMROIDEM' => 0,
                    'ACMVOIMOD' => 'N',
                    'ACMVOITCMB' => 0,
                    'ACMVOIFOB' => ' ',
                    'ACMROIFP' => 0,
                    'ACMROIFM' => 0,
                    'ACMROIFI' => 0,
                    'ACMROIFG' => 0,
                    'ACACTLID' => '          ',
                    'ACACSGID' => '          ',
                    'ACACANID' => '          ',
                    'ACMROISFJ' => 0,
                    'ACMROIFPR' => '                    ',
                    'ACMROILOTE' => '                              ',
                    'ACMROIUB1' => '          ',
                    'ACMROIUB2' => '          ',
                    'ACMROIUB4' => '          ',
                    'ACMROIPAD1' => 0,
                    'ACMROIPFC1' => '                    ',
                    'ACMROIPAD2' => 0,
                    'ACMROIPFC2' => '                    ',
                    'ACMROIPAD3' => 0,
                    'ACMROIPFC3' => '   ',
                    'ACMROIDCP1' => 0,
                    'ACMROICIP1' => 0,
                    'ACMROIOBS' => '',
                    'ACMROIYY' => 0,
                    'ACMROICTGOID' => '               ',
                    'ACMROICTGODSC' => '                                        ',
                    'ACMROIACTSID' => 0,
                    'ACMROIACTSDSC' => '                                                            ',
                    'ACAUTRID' => '          ',
                    'ACMROILDP1' => 0,
                    'ACMROIDOI4' => '   ',
                    'ACMROIDOC4' => 0,
                    'ACMROIQTYC' => 0,
                    'ACMROIQTYQ' => 0,
                    'ACMROIQTYT' => 0,
                    'ACACCRLT' => ' ',
                    'ACMROIFP2' => 0,
                    'ACMROIFM2' => 0,
                    'ACMROIFI2' => 0,
                    'ACMROIFG2' => 0,
                    'ACMROICCAD' => '   ',
                    'ACMROIRET' => ' ',
                    'ACMROIUGPT' => '          ',
                    'ACMROICEP' => 0,
                    'ACMROIMEP' => 0,
                    'ACMROIBGP' => ' ',
                    'ACMROIEMTID' => 0,
                    'ACMROIEMTDSC' => '                                                                                                                        ',
                    'ACMROICHOF' => '                                                            ',
                    'ACMROIGUIA' => '                    ',
                    'ACMROIPLAC' => '               ',
                    'ACMROIRUTA' => '     ',
                    'ACMROINECO' => '                    ',
                    'ACMROIOBST' => '                                                                                                                        ',
                    'ACMROIQTO' => 0,
                    'ACRCOICGJR01ID' => '               ',
                    'ACRCOICGJR02ID' => '               ',
                    'ACRCOICGJR03ID' => '               ',
                    'ACRCOICGJR04ID' => '               ',
                    'ACRCOICGJR05ID' => '               ',
                    'ACRCOICGJR06ID' => '               ',
                    'ACRCOICGJR07ID' => '               ',
                    'ACRCOICGJR08ID' => '               ',
                    'ACRCOICGJR09ID' => '               ',
                    'ACMROIABC' => 'A-A-A',
                    'ACMROITDP1' => '   ',
                    'ACMROIUB3' => '          ',
                    'ACMROICAN' => ' ',
                ];

                $inserted = DB::connection($connection)->table('acmroi')->insert($insertData);

                if (!$inserted) {
                    Log::error("Fallo al insertar en acmroi para INPRODID {$partida['ACMVOIPRID']} y ACMVOILIN {$acmroilin}");
                }
            }

            Log::info("Insertar en ACMROI completado con éxito.");
        } catch (\Exception $e) {
            Log::error("Error al insertar en acmroi: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateInsdos($storeId, $productId, $cantidadRecibida, $costoTotal)
    {
        $connectionLocal = 'FD04';
        $connectionRemota = trim(Auth::user()->costCenters->first()->cost_center_id); // Eliminamos espacios en blanco

        $connections = [$connectionLocal];

        // Verificar si la conexión remota es FD09 o FD10, después de limpiar los espacios
        if (in_array($connectionRemota, ['FD09', 'FD10'])) {
            $connections[] = $connectionRemota;
        } else {
            Log::warning("No se encontró una conexión remota válida para el centro de costo {$connectionRemota}");
        }

        try {
            foreach ($connections as $connection) {
                Log::info("Intentando actualizar/insertar en INSDOS en la conexión: {$connection}");

                // Obtener el idioma de la base de datos
                $dbLanguage = DB::connection($connection)->select(DB::raw("SELECT @@language AS 'Idioma'"))[0]->Idioma;

                // Definir el formato de la fecha según el idioma
                $fechaActual = now();
                if (strtolower($dbLanguage) === 'english') {
                    $fechaActual = $fechaActual->format('m/d/Y H:i:s');
                } else {
                    $fechaActual = $fechaActual->format('d/m/Y H:i:s');
                }

                // Verificar si el registro ya existe
                $existingRecord = DB::connection($connection)->table('insdos')
                    ->where('INALMNID', $storeId)
                    ->where('INPRODID', $productId)
                    ->first();

                if ($existingRecord) {
                    DB::connection($connection)->table('insdos')
                        ->where('INALMNID', $storeId)
                        ->where('INPRODID', $productId)
                        ->update([
                            'INSDOSQDS' => DB::raw('INSDOSQDS + ' . (float) $cantidadRecibida),
                            'INSDOSVAL' => DB::raw('INSDOSVAL + ' . (float) $costoTotal),
                        ]);

                    Log::info("Registro actualizado en INSDOS en conexión {$connection} para el producto {$productId} con cantidad recibida {$cantidadRecibida} y costo total {$costoTotal}.");
                } else {
                    DB::connection($connection)->table('insdos')->insert([
                        'INALMNID' => $storeId,
                        'INPRODID' => $productId,
                        'INSDOSQDS' => $cantidadRecibida,
                        'INSDOSVAL' => $costoTotal,
                        'INSDOSFTRN' => $fechaActual,  // Fecha con formato basado en idioma
                    ]);

                    Log::info("Nuevo registro insertado en INSDOS en conexión {$connection} para el producto {$productId} con cantidad recibida {$cantidadRecibida} y costo total {$costoTotal}.");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al actualizar insdos en conexión {$connection}: " . $e->getMessage());
            throw $e;
        }
    }
    public function insertCGMVCN($validatedData, $totalPartidas)
    {
        $centroCosto = trim(Auth::user()->costCenters->first()->cost_center_id); // Centro de costo asociado al usuario
    
        // Conexiones de base de datos (local y remota)
        $connectionLocal = 'FD04'; // Local
        $connectionRemota = $centroCosto; // Remota (centro de costo)
    
        // Verifica si la conexión remota es válida
        $connections = [$connectionLocal];
        if (in_array($connectionRemota, ['FD09', 'FD10'])) {
            $connections[] = $connectionRemota;
        }
    
        // Fecha de recepción
        $fechaRecepcion = Carbon::now(); // Fecha actual para la recepción
        $numRCN = $validatedData['document_number1']; // Número de RCN generado
        $usuario = Auth::user()->username; // Nombre del usuario logueado
        $totalLineas = $totalPartidas / 2; // Número de líneas es el total de partidas dividido entre 2
    
        foreach ($connections as $connection) {
            try {
                // Obtener el idioma de la base de datos para formatear las fechas
                $dbLanguage = DB::connection($connection)->select(DB::raw("SELECT @@language AS 'Idioma'"))[0]->Idioma;
    
                // Formatear las fechas según el idioma de la base de datos
                $fechaActualFormatted = $this->getFormattedDate($fechaRecepcion, $dbLanguage);
                $fechaDefaultFormatted = $this->getFormattedDefaultDate($dbLanguage);
    
                // Inserción en la tabla CGMVCN
                DB::connection($connection)->table('CGMVCN')->insert([
                    'CNCIASID' => 1,
                    'CNTDOCID' => 'RCN',
                    'CGMVCNDOC' => $numRCN, // Número de RCN
                    'CGMVCNFCNT' => $fechaActualFormatted, // Fecha de recepción
                    'CGMVCNFTRN' => $fechaActualFormatted, // Fecha de transacción
                    'CGMVCNFCON' => $fechaActualFormatted, // Fecha de confirmación
                    'CGMVCNFAHD' => $fechaActualFormatted, // Fecha de auditoría
                    'CGMVCNFEHR' => $fechaActualFormatted, // Fecha de recepción
                    'CGMVCNPER' => $fechaRecepcion->format('m'), // Mes de la recepción
                    'CGMVCNCON' => 'RECEPCION DE MATERIAL', // Descripción de la recepción
                    'CGMVCNPOST' => 'N', // No posteado
                    'CGMVCNPMD' => 'N', // No proceso manual
                    'CNCMNMID' => 'MXP', // Moneda
                    'CGMVCNMOD' => 'N', // No modificado
                    'CGMVCNTCMB' => 0, // No intercambio
                    'CGMVCNPPP' => 'C', // Tipo de pedido
                    'CGMVCNMPP' => 0, // No modificación posterior
                    'CGMVCNMDP' => 'CPA', // Código de método de pago
                    'CGMVCNCAN' => 'N', // No cancelado
                    'CGMVCNULIN' => $totalLineas, // Número de partidas divididas entre 2
                    'CGMV01ID' => '          ', // Campo reservado
                    'CGMV02ID' => '          ', // Campo reservado
                    'CGMV03ID' => '          ', // Campo reservado
                    'CGMV04ID' => '          ', // Campo reservado
                    'CGMV05ID' => '          ', // Campo reservado
                    'CGMV06ID' => 0, // Campo reservado
                    'CGMV07ID' => 0, // Campo reservado
                    'CGMV08ID' => 0, // Campo reservado
                    'CGMV09ID' => 0, // Campo reservado
                    'CGMV10ID' => 0, // Campo reservado
                    'CGMVCNTDC' => '   ', // Campo reservado
                    'CGMVCNNDC' => 0, // Campo reservado
                    'CGCJ01ID' => '               ', // Campo reservado
                    'CGCJ02ID' => '               ', // Campo reservado
                    'CGCJ03ID' => '               ', // Campo reservado
                    'CGCJ04ID' => '               ', // Campo reservado
                    'CGCJ05ID' => '               ', // Campo reservado
                    'CGCJ06ID' => '               ', // Campo reservado
                    'CGCJ07ID' => '               ', // Campo reservado
                    'CGCJ08ID' => '               ', // Campo reservado
                    'CGCJ09ID' => '               ', // Campo reservado
                    'CGCJ10ID' => '               ', // Campo reservado
                    'CGCJ11ID' => '               ', // Campo reservado
                    'CGCJ12ID' => '               ', // Campo reservado
                    'CGCJ13ID' => '               ', // Campo reservado
                    'CGCJ14ID' => '               ', // Campo reservado
                    'CGCJ15ID' => '               ', // Campo reservado
                    'CGMVCNUSRO' => 0, // Campo reservado
                    'CGMVCNUSRU' => 0, // Campo reservado
                    'CGMVCNWRKS' => '               ', // Campo reservado
                    'CGMVCNDOCP' => 0, // Campo reservado
                    'CGMVCNTDP' => '   ', // Campo reservado
                    'CGMV11ID' => '          ', // Campo reservado
                    'CGMV12ID' => '          ', // Campo reservado
                    'CGMV13ID' => '          ', // Campo reservado
                    'CGMV14ID' => '          ', // Campo reservado
                    'CGMV15ID' => '          ', // Campo reservado
                    'CGMV16ID' => '          ', // Campo reservado
                    'CGMV17ID' => '          ', // Campo reservado
                    'CGMV18ID' => '          ', // Campo reservado
                    'CGMV19ID' => '          ', // Campo reservado
                    'CGMV20ID' => '          ', // Campo reservado
                    'CGMVCNTDPO' => '   ', // Campo reservado
                    'CGMVCNNDPO' => 0, // Campo reservado
                    'CGMVCNFCPO' => $fechaDefaultFormatted, // Fecha por defecto
                    'CGMVCNUELAB' => $usuario, // Usuario logueado
                    'CGMVCNUPOST' => '          ', // Campo reservado
                    'CGMVCNFPOST' => $fechaDefaultFormatted, // Fecha por defecto
                    'CGMVCNPELM' => 0, // Campo reservado
                    'CGMVCNUIMP' => '          ', // Campo reservado
                    'CGMVCNMIMP' => '', // No NULL pero vacío
                    'CGMVCNCHQ' => '          ', // Campo reservado
                    'CGMVCNUMOD' => '          ', // Campo reservado
                    'CGMVCNFMOD' => $fechaDefaultFormatted, // Fecha por defecto
                    'CGMVCNUUPOST' => '          ', // Campo reservado
                    'CGMVCNFUPOST' => $fechaDefaultFormatted, // Fecha por defecto
                    'CGMVCNXML' => ' ', // Campo reservado
                    'CGMVCNBAN' => 0, // Campo reservado
                    'CGMVCNCTAB' => '', // No NULL pero vacío
                    'CGMVCNNCH' => '', // No NULL pero vacío
                    'CGMVCNIDPG' => '', // No NULL pero vacío
                    'CGMVCNREF' => '', // No NULL pero vacío
                    'CGMVCNDIR' => 0, // Campo reservado
                    'CGMVCNBANO' => 0, // Campo reservado
                    'CGMVCNCTAO' => '', // No NULL pero vacío
                    'CGMVCNBMAN' => 0, // Campo reservado
                ]);
    
                Log::info("Registro insertado en CGMVCN en la conexión {$connection} con número de RCN: {$numRCN}");
    
            } catch (\Exception $e) {
                Log::error("Error al insertar en CGMVCN en la conexión {$connection}: " . $e->getMessage());
                throw $e;
            }
        }
    }
    

    public function getFormattedDate($fecha, $dbLanguage)
    {
        if (strtolower($dbLanguage) == 'us_english') {
            return $fecha->format('Y-m-d H:i:s');
        } else {
            // Por ejemplo, en Español se podría requerir el formato d/m/Y
            return $fecha->format('d/m/Y H:i:s');
        }
    }
    
    public function getFormattedDefaultDate($dbLanguage)
    {
        if (strtolower($dbLanguage) == 'us_english') {
            return '1753-01-01 00:00:00';
        } else {
            return '01/01/1753 00:00:00';
        }
    }
    


public function receiptOrder(Request $request, $ACMVOIDOC)
{
    try {
        $input = $request->all();

        if (isset($input['precio_unitario'])) {
            foreach ($input['precio_unitario'] as $key => $precio) {
                $input['precio_unitario'][$key] = preg_replace('/[\$,]/', '', $precio);
            }
        }

        if (isset($input['cantidad_recibida'])) {
            foreach ($input['cantidad_recibida'] as $key => $cantidad) {
                $input['cantidad_recibida'][$key] = preg_replace('/[\$,]/', '', $cantidad);
            }
        }

        if (isset($input['freight'])) {
            $input['freight'] = preg_replace('/[\$,]/', '', $input['freight']);
        }

        if (isset($input['total_cost'])) {
            $input['total_cost'] = preg_replace('/[\$,]/', '', $input['total_cost']);
        } else {
            $input['total_cost'] = 0;
        }

        $order = Order::where('ACMVOIDOC', $ACMVOIDOC)->first();
        $provider = Providers::where('CNCDIRID', $order->CNCDIRID)->first();

        if (!$order || !$provider) {
            return response()->json(['success' => false, 'message' => 'Orden o proveedor no encontrado.']);
        }

        $input['supplier_name'] = $provider->CNCDIRNOM;
        $input['reference_type'] = $request->input('reference_type', '');
        $input['store'] = $order->ACMVOIALID;
        $input['reference'] = $request->input('reference', '');
        $input['reception_date'] = $request->input('reception_date', now()->toDateString());

        // Validación de datos
        $validatedData = $request->merge($input)->validate([
            'document_type' => 'required|string',
            'document_number' => 'required|string',
            'document_type1' => 'required|string',
            'document_number1' => 'required|string',
            'freight' => 'nullable|numeric',
            'total_cost' => 'nullable|numeric',
            'carrier_number' => 'nullable|string|max:255',
            'carrier_name' => 'nullable|string|max:255',
            'supplier_name' => 'required|string|max:255',
            'reference_type' => 'required|string|max:255',
            'store' => 'required|string|max:255',
            'reference' => 'required|string|max:255',
            'reception_date' => 'required|date',
            'cantidad_recibida.*' => 'nullable|numeric|regex:/^\d+(\.\d{1,4})?$/',
            'precio_unitario.*' => 'nullable|numeric|regex:/^\d+(\.\d{1,4})?$/',
        ], [
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'supplier_name.required' => 'El nombre del proveedor es obligatorio.',
            'reference_type.required' => 'El tipo de referencia es obligatorio.',
            'store.required' => 'El almacén es obligatorio.',
            'reference.required' => 'La referencia es obligatoria.',
            'reception_date.required' => 'La fecha de recepción es obligatoria.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'regex' => 'El campo :attribute no puede contener más de 4 decimales.',
        ]);

        Log::info('Datos recibidos en receiptOrder:', $validatedData);

        DB::beginTransaction();

        try {
            $partidasCGMVCN1 = [];

            foreach ($validatedData['cantidad_recibida'] as $index => $cantidadRecibida) {
                if ($cantidadRecibida === null || $cantidadRecibida === '') {
                    continue;
                }

                if (!is_numeric($cantidadRecibida) || $cantidadRecibida <= 0) {
                    Log::warning("Valor de cantidad_recibida no válido para el índice {$index}. Se omite la actualización.");
                    continue;
                }

                $precioUnitario = $validatedData['precio_unitario'][$index] ?? 0;
                if ($precioUnitario === null || $precioUnitario === '' || !is_numeric($precioUnitario) || $precioUnitario <= 0) {
                    Log::warning("Precio unitario no válido en la línea {$index}. Se omite la actualización.");
                    continue;
                }

                $cantidadRecibida = number_format((float) bcdiv($cantidadRecibida, '1', 4), 4, '.', '');
                $precioUnitario = number_format((float) bcdiv($precioUnitario, '1', 4), 4, '.', '');

                $cantidadSolicitada = (float) $request->input('acmvoiqtp')[$index] > 0 ? $request->input('acmvoiqtp')[$index] : $request->input('acmvoiqto')[$index];
                $cantidadSolicitada = number_format((float) bcdiv($cantidadSolicitada, '1', 4), 4, '.', '');

                DB::table('ACMVOR1')
                    ->where('ACMVOIDOC', $ACMVOIDOC)
                    ->where('ACMVOILIN', $request->input('acmvoilin')[$index])
                    ->update([
                        'ACMVOIQTR' => DB::raw('ACMVOIQTR + ' . $cantidadRecibida),
                        'ACMVOIQTP' => $cantidadSolicitada - $cantidadRecibida,
                    ]);

                // Preparar la información de cada partida para insertarla en CGMVCN1
                $partidasCGMVCN1[] = [
                    'cantidad_recibida' => $cantidadRecibida,
                    'costo_total' => $cantidadRecibida * $precioUnitario,
                    'unidad_medida' => $request->input('acmvoiumt')[$index],
                    'producto_id' => $request->input('acmvoiprid')[$index],
                    'producto_desc' => $request->input('acmvoiprds')[$index],
                    'producto_sku' => $request->input('acmvoiprid')[$index], // Asumo que el SKU es el mismo que el ID del producto
                    'producto_inprodi3' => $request->input('inprodi3')[$index] ?? '', // Se debe obtener de la tabla de productos si es necesario
                ];
            }

            // Inserción en la tabla de fletes, si aplica
            if (isset($validatedData['freight']) && $validatedData['freight'] > 0) {
                if (!$this->insertFreight($validatedData, $provider)) {
                    throw new \Exception("Error al insertar datos de flete.");
                }
            }

            // Insertar partidas en ACMROI (lógica interna para las partidas)
            $this->insertPartidas($request->all(), $validatedData['cantidad_recibida'], $validatedData['precio_unitario'], $order, $provider);

            // Insertar en CGMVCN (cabecera de la recepción)
            $this->insertCGMVCN($validatedData, count($validatedData['cantidad_recibida']));

            // Insertar en CGMVCN1 (detalle de la recepción) usando la información preparada
            $this->insertCGMVCN1($validatedData, $partidasCGMVCN1);

            // Confirmar la transacción
            DB::commit();

            Log::info('Recepción registrada con éxito en la base de datos.');

            return response()->json([
                'success' => true,
                'message' => 'Recepción registrada con éxito.',
                'ACMROINDOC' => $validatedData['document_number1'],
                'ACMROIDOC' => $order->ACMVOIDOC
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en la recepción de la orden: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al registrar la recepción.']);
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Errores de validación:', $e->errors());
        return response()->json([
            'success' => false,
            'errors' => $e->errors()
        ], 422);
    }
}


public function insertCGMVCN1($receptionData, $partidas)
{
    $centroCosto = trim(Auth::user()->costCenters->first()->cost_center_id); // Centro de costo asociado al usuario

    // Conexiones de base de datos (local y remota)
    $connectionLocal = 'FD04'; // Local
    $connectionRemota = $centroCosto; // Remota (centro de costo)

    // Verifica si la conexión remota es válida
    $connections = [$connectionLocal];
    if (in_array($connectionRemota, ['FD09', 'FD10'])) {
        $connections[] = $connectionRemota;
    }

    $fechaRecepcion = Carbon::now(); // Fecha actual
    $numRCN = $receptionData['document_number1']; // Número de RCN generado
    $usuario = Auth::user()->username; // Nombre del usuario logueado

    foreach ($connections as $connection) {
        foreach ($partidas as $index => $partida) {
            $costoTotal = $partida['costo_total']; // Costo total de la partida
            $cantidadRecibida = $partida['cantidad_recibida']; // Cantidad recepcionada
            $unidadMedida = $partida['unidad_medida']; // Unidad de medida
            $productoId = $partida['producto_id']; // ID del producto
            $productoDesc = $partida['producto_desc']; // Descripción del producto
            $productoSKU = $partida['producto_sku']; // SKU del producto
            $productoInprodi3 = $partida['producto_inprodi3']; // INPRODI3 de la tabla INPROD

            try {
                // Obtener el idioma de la base de datos para formatear las fechas
                $dbLanguage = DB::connection($connection)->select(DB::raw("SELECT @@language AS 'Idioma'"))[0]->Idioma;

                // Formatear la fecha según el idioma de la base de datos
                if ($dbLanguage === 'us_english') {
                    $fechaActualFormatted = $fechaRecepcion->format('Y-m-d H:i:s'); // Formato esperado en inglés (YYYY-MM-DD)
                } else {
                    $fechaActualFormatted = $fechaRecepcion->format('d/m/Y H:i:s'); // Formato esperado en español (DD/MM/YYYY)
                }

                $fechaDefaultFormatted = '1753-01-01 00:00:00'; // Fecha por defecto

                // Primer registro CGMVCN1 (costo total negativo)
                DB::connection($connection)->table('CGMVCN1')->insert([
                    'CNCIASID' => 1,
                    'CNTDOCID' => 'RCN',
                    'CGMVCNDOC' => $numRCN,
                    'CGMVCNFCNT' => $fechaActualFormatted, // Fecha de recepción
                    'CGMVCNLIN' => ($index * 2) + 1, // Primera línea (incremento por partida)
                    'CGMVCNLIB' => 'NL',
                    'CGUNNGID' => 1001, // Primer registro 1001
                    'CGCTASI1' => 4415, // Primer valor específico
                    'CGCTASI2' => '0001', // Primer valor específico
                    'CGCTASI3' => '            ', // Campo vacío
                    'CGCTASCIAS' => 0, // Campo reservado
                    'CGMVCNFADT' => $fechaActualFormatted, // Fecha de recepción
                    'CGTSBLID' => ' ', // Campo reservado
                    'CGMVCNSBL' => 0, // Campo reservado
                    'CGMVCNSBLD' => '                                                            ', // Campo vacío
                    'CGMVCNTREF' => '   ', // Campo reservado
                    'CGMVCNNREF' => 0, // Campo reservado
                    'CGMVCNDREF' => '   ', // Campo reservado
                    'CGMVCNPRC' => 0, // Campo reservado
                    'CGMVCNSIG' => ' ', // Campo reservado
                    'CGMVCNMNT' => -$costoTotal, // Costo total negativo
                    'CGMVCNCGO' => 0, // Primer registro tiene costo 0
                    'CGMVCNABN' => $costoTotal, // Costo total
                    'CGMVCNQTY' => $cantidadRecibida, // Cantidad recepcionada
                    'CGMVCNUM' => $unidadMedida, // Unidad de medida
                    'CGMVCNMON' => 'MXP', // Moneda MXP
                    'CGMVCNTCM2' => 0, // Campo reservado
                    'CGMVCNINP1' => $productoId, // ID del producto
                    'CGMVCNINPD' => $productoDesc, // Descripción del producto
                    'CGMVCNINP2' => $productoSKU, // SKU del producto
                    'CGMVCNINP3' => $productoInprodi3, // INPRODI3 del producto
                    'CGMVCNPOST1' => 'N', // Posteo
                    'CGMD01ID' => 'REQ       ', // Campo específico
                    'CGMD02ID' => '          ', // Campo reservado
                    'CGMD03ID' => '          ', // Campo reservado
                    'CGMD04ID' => '          ', // Campo reservado
                    'CGMD05ID' => 'MXP       ', // Moneda MXP
                    'CGMD06ID' => 0, // Campo reservado
                    'CGMD07ID' => 0, // Campo reservado
                    'CGMD08ID' => 0, // Campo reservado
                    'CGMD09ID' => 0, // Campo reservado
                    'CGMD10ID' => 0, // Campo reservado
                    'CGDJ01ID' => '               ', // Campo reservado
                    'CGDJ02ID' => '               ', // Campo reservado
                    'CGDJ03ID' => '               ', // Campo reservado
                    'CGDJ04ID' => '               ', // Campo reservado
                    'CGDJ05ID' => '               ', // Campo reservado
                    'CGDJ06ID' => '               ', // Campo reservado
                    'CGDJ07ID' => '               ', // Campo reservado
                    'CGDJ08ID' => '               ', // Campo reservado
                    'CGDJ09ID' => '               ', // Campo reservado
                    'CGDJ10ID' => '               ', // Campo reservado
                    'CGDJ11ID' => '               ', // Campo reservado
                    'CGDJ12ID' => '               ', // Campo reservado
                    'CGDJ13ID' => '               ', // Campo reservado
                    'CGDJ14ID' => '               ', // Campo reservado
                    'CGDJ15ID' => '               ', // Campo reservado
                    'CGMVCNALIAS' => '.', // Alias
                    'CGMD11ID' => '          ', // Campo reservado
                    'CGMD12ID' => '          ', // Campo reservado
                    'CGMD13ID' => '          ', // Campo reservado
                    'CGMD14ID' => '          ', // Campo reservado
                    'CGMD15ID' => '          ', // Campo reservado
                    'CGMD16ID' => '          ', // Campo reservado
                    'CGMD17ID' => '          ', // Campo reservado
                    'CGMD18ID' => '          ', // Campo reservado
                    'CGMD19ID' => '          ', // Campo reservado
                    'CGMD20ID' => '          ', // Campo reservado
                    'CGMVCNTDP1' => '   ', // Campo reservado
                    'CGMVCNDOCP1' => 0, // Campo reservado
                    'CGMVCNCAND' => ' ', // Campo reservado
                    'CGMVCNMXAUT' => 0.0000, // Campo reservado
                ]);

                // Segundo registro CGMVCN1 (costo total positivo)
                DB::connection($connection)->table('CGMVCN1')->insert([
                    'CNCIASID' => 1,
                    'CNTDOCID' => 'RCN',
                    'CGMVCNDOC' => $numRCN,
                    'CGMVCNFCNT' => $fechaActualFormatted, // Fecha de recepción
                    'CGMVCNLIN' => ($index * 2) + 2, // Segunda línea (incremento por partida)
                    'CGMVCNLIB' => 'NL',
                    'CGUNNGID' => 1004, // Segundo registro 1004
                    'CGCTASI1' => 1102, // Segundo valor específico
                    'CGCTASI2' => '0002', // Segundo valor específico
                    'CGCTASI3' => '            ', // Campo vacío
                    'CGCTASCIAS' => 0, // Campo reservado
                    'CGMVCNFADT' => $fechaActualFormatted, // Fecha de recepción
                    'CGTSBLID' => ' ', // Campo reservado
                    'CGMVCNSBL' => 0, // Campo reservado
                    'CGMVCNSBLD' => '                                                            ', // Campo vacío
                    'CGMVCNTREF' => '   ', // Campo reservado
                    'CGMVCNNREF' => 0, // Campo reservado
                    'CGMVCNDREF' => '   ', // Campo reservado
                    'CGMVCNPRC' => 0, // Campo reservado
                    'CGMVCNSIG' => ' ', // Campo reservado
                    'CGMVCNMNT' => $costoTotal, // Costo total positivo
                    'CGMVCNCGO' => $costoTotal, // Costo total
                    'CGMVCNABN' => 0, // Costo total 0 en segundo registro
                    'CGMVCNQTY' => $cantidadRecibida, // Cantidad recepcionada
                    'CGMVCNUM' => $unidadMedida, // Unidad de medida
                    'CGMVCNMON' => 'MXP', // Moneda MXP
                    'CGMVCNTCM2' => 0, // Campo reservado
                    'CGMVCNINP1' => $productoId, // ID del producto
                    'CGMVCNINPD' => $productoDesc, // Descripción del producto
                    'CGMVCNINP2' => $productoSKU, // SKU del producto
                    'CGMVCNINP3' => $productoInprodi3, // INPRODI3 del producto
                    'CGMVCNPOST1' => 'N', // Posteo
                    'CGMD01ID' => 'REQ       ', // Campo específico
                    'CGMD02ID' => '          ', // Campo reservado
                    'CGMD03ID' => '          ', // Campo reservado
                    'CGMD04ID' => '          ', // Campo reservado
                    'CGMD05ID' => 'MXP       ', // Moneda MXP
                    'CGMD06ID' => 0, // Campo reservado
                    'CGMD07ID' => 0, // Campo reservado
                    'CGMD08ID' => 0, // Campo reservado
                    'CGMD09ID' => 0, // Campo reservado
                    'CGMD10ID' => 0, // Campo reservado
                    'CGDJ01ID' => '               ', // Campo reservado
                    'CGDJ02ID' => '               ', // Campo reservado
                    'CGDJ03ID' => '               ', // Campo reservado
                    'CGDJ04ID' => '               ', // Campo reservado
                    'CGDJ05ID' => '               ', // Campo reservado
                    'CGDJ06ID' => '               ', // Campo reservado
                    'CGDJ07ID' => '               ', // Campo reservado
                    'CGDJ08ID' => '               ', // Campo reservado
                    'CGDJ09ID' => '               ', // Campo reservado
                    'CGDJ10ID' => '               ', // Campo reservado
                    'CGDJ11ID' => '               ', // Campo reservado
                    'CGDJ12ID' => '               ', // Campo reservado
                    'CGDJ13ID' => '               ', // Campo reservado
                    'CGDJ14ID' => '               ', // Campo reservado
                    'CGDJ15ID' => '               ', // Campo reservado
                    'CGMVCNALIAS' => '.', // Alias
                    'CGMD11ID' => '          ', // Campo reservado
                    'CGMD12ID' => '          ', // Campo reservado
                    'CGMD13ID' => '          ', // Campo reservado
                    'CGMD14ID' => '          ', // Campo reservado
                    'CGMD15ID' => '          ', // Campo reservado
                    'CGMD16ID' => '          ', // Campo reservado
                    'CGMD17ID' => '          ', // Campo reservado
                    'CGMD18ID' => '          ', // Campo reservado
                    'CGMD19ID' => '          ', // Campo reservado
                    'CGMD20ID' => '          ', // Campo reservado
                    'CGMVCNTDP1' => '   ', // Campo reservado
                    'CGMVCNDOCP1' => 0, // Campo reservado
                    'CGMVCNCAND' => ' ', // Campo reservado
                    'CGMVCNMXAUT' => 0.0000, // Campo reservado
                ]);

                Log::info("Registros insertados en CGMVCN1 en la conexión {$connection} para la partida {$index}");

            } catch (\Exception $e) {
                Log::error("Error al insertar en CGMVCN1 en la conexión {$connection}: " . $e->getMessage());
                throw $e;
            }
        }
    }
}




}
