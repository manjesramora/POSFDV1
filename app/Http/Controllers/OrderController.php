<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Providers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

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


    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $centrosCostosIds = $user->costCenters->pluck('cost_center_id')->toArray();
        $query = Order::query();

        if (!empty($centrosCostosIds)) {
            $query->whereIn('ACMVOIALID', $centrosCostosIds);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Definir la fecha límite de 6 meses
        $sixMonthsAgo = Carbon::now()->subMonths(6);

        // Validar que las fechas de búsqueda no sean mayores a 6 meses
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            // Verifica si alguna de las fechas está fuera del rango de 6 meses
            if ($start->lt($sixMonthsAgo) || $end->lt($sixMonthsAgo)) {
                return redirect()->route('orders')->with('error', 'El rango de fechas no puede ser mayor a 6 meses desde la fecha actual.');
            }

            $query->whereBetween('ACMVOIFDOC', [$startDate, $endDate]);
        } else {
            // Si no se proporcionaron fechas, asegura que los resultados sean dentro de los últimos 6 meses
            $query->where('ACMVOIFDOC', '>=', $sixMonthsAgo);
        }

        // Definir valores predeterminados para evitar errores de variables indefinidas
        $sortColumn = $request->input('sortColumn', 'ACMVOIDOC');
        $sortDirection = $request->input('sortDirection', 'desc');

        // Mostrar la pantalla vacía hasta que se aplique el filtro de búsqueda
        if (!$startDate && !$endDate && !$request->filled('ACMVOIDOC') && !$request->filled('CNCDIRID') && !$request->filled('CNCDIRNOM')) {
            $orders = collect(); // Pantalla vacía hasta que se aplique el filtro
        } else {
            // Filtros adicionales
            if ($request->filled('ACMVOIDOC')) {
                $query->where('ACMVOIDOC', $request->input('ACMVOIDOC'));
            }

            if ($request->filled('CNCDIRID')) {
                $query->where('ACMVOR.CNCDIRID', $request->input('CNCDIRID'));
            }

            if ($request->filled('CNCDIRNOM')) {
                $query->whereHas('provider', function ($q) use ($request) {
                    $q->where('CNCDIR.CNCDIRNOM', 'like', '%' . $request->input('CNCDIRNOM') . '%');
                });
            }

            // Sorting logic
            $sortableColumns = ['CNTDOCID', 'ACMVOIDOC', 'CNCDIRID', 'CNCDIRNOM', 'ACMVOIFDOC', 'ACMVOIALID'];

            if (in_array($sortColumn, $sortableColumns)) {
                if ($sortColumn == 'CNCDIRNOM') {
                    $query->join('CNCDIR', 'ACMVOR.CNCDIRID', '=', 'CNCDIR.CNCDIRID')
                        ->orderBy('CNCDIR.CNCDIRNOM', $sortDirection);
                } else {
                    $query->orderBy($sortColumn, $sortDirection);
                }
            } else {
                $query->orderBy('ACMVOIDOC', 'desc');
            }

            // Filtrar solo órdenes con movimientos pendientes de recepción
            $query->whereExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('ACMVOR1')
                    ->whereRaw('ACMVOR1.ACMVOIDOC = ACMVOR.ACMVOIDOC')
                    ->whereRaw('ACMVOR1.ACMVOIQTO > ACMVOR1.ACMVOIQTR');
            });

            $orders = $query->paginate(30);
        }

        if ($request->ajax()) {
            return view('orders_table', compact('orders', 'sortColumn', 'sortDirection'))->render();
        }

        return view('orders', compact('orders', 'sortColumn', 'sortDirection', 'startDate', 'endDate'));
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

    public function receiptOrder(Request $request, $ACMVOIDOC)
    {
        try {
            // Captura todos los datos del request
            $input = $request->all();

            // Limpieza de los campos de precios y cantidades para asegurar que sean números
            if (isset($input['precio_unitario'])) {
                foreach ($input['precio_unitario'] as $key => $precio) {
                    // Elimina símbolos de moneda y comas
                    $input['precio_unitario'][$key] = preg_replace('/[\$,]/', '', $precio);
                }
            }

            if (isset($input['cantidad_recibida'])) {
                foreach ($input['cantidad_recibida'] as $key => $cantidad) {
                    // Elimina comas y otros caracteres no numéricos
                    $input['cantidad_recibida'][$key] = preg_replace('/[\$,]/', '', $cantidad);
                }
            }

            if (isset($input['freight'])) {
                // Elimina símbolos de moneda y comas del flete
                $input['freight'] = preg_replace('/[\$,]/', '', $input['freight']);
            }

            if (isset($input['total_cost'])) {
                // Elimina símbolos de moneda y comas del costo total
                $input['total_cost'] = preg_replace('/[\$,]/', '', $input['total_cost']);
            } else {
                // Establecer el costo total a 0 si no está presente
                $input['total_cost'] = 0;
            }

            // Añadir el nombre del proveedor, el tipo de referencia, el almacén y la fecha de recepción desde la base de datos
            $order = Order::where('ACMVOIDOC', $ACMVOIDOC)->first();
            $provider = Providers::where('CNCDIRID', $order->CNCDIRID)->first();

            if (!$order || !$provider) {
                return response()->json(['success' => false, 'message' => 'Orden o proveedor no encontrado.']);
            }

            // Añadir el nombre del proveedor, tipo de referencia, almacén, referencia y fecha de recepción al input
            $input['supplier_name'] = $provider->CNCDIRNOM;
            $input['reference_type'] = $request->input('reference_type', '');
            $input['store'] = $order->ACMVOIALID; // Obtén el almacén de la orden
            $input['reference'] = $request->input('reference', ''); // Asegurar que la referencia se envíe o esté vacía por defecto
            $input['reception_date'] = $request->input('reception_date', now()->toDateString()); // Establecer la fecha actual si no se proporciona

            // Validar los datos limpios
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
                'reception_date' => 'required|date', // Asegúrate de que la fecha de recepción esté presente
                'cantidad_recibida.*' => 'nullable|numeric|regex:/^\d+(\.\d{1,4})?$/', // Hasta 4 decimales
                'precio_unitario.*' => 'nullable|numeric|regex:/^\d+(\.\d{1,4})?$/', // Hasta 4 decimales
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Registro de errores de validación
            Log::error('Errores de validación:', $e->errors());
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }

        Log::info('Datos recibidos en receiptOrder:', $validatedData);

        DB::beginTransaction();

        try {
            foreach ($validatedData['cantidad_recibida'] as $index => $cantidadRecibida) {
                if ($cantidadRecibida === null || $cantidadRecibida === '') {
                    continue;
                }

                if (!is_numeric($cantidadRecibida) || $cantidadRecibida <= 0) {
                    Log::warning("Valor de `cantidad_recibida` no válido para el índice {$index}. Se omite la actualización.");
                    continue;
                }

                $precioUnitario = $validatedData['precio_unitario'][$index] ?? 0;
                if ($precioUnitario === null || $precioUnitario === '' || !is_numeric($precioUnitario) || $precioUnitario <= 0) {
                    Log::warning("Precio unitario no válido en la línea {$index}. Se omite la actualización.");
                    continue;
                }

                // Truncar los valores a 4 decimales sin redondear
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
            }

            // Registrar el flete solo si se proporcionó un valor de flete
            if (isset($validatedData['freight']) && $validatedData['freight'] > 0) {
                if (!$this->insertFreight($validatedData, $provider)) {
                    throw new \Exception("Error al insertar datos de flete.");
                }
            }

            $this->insertPartidas($request->all(), $validatedData['cantidad_recibida'], $validatedData['precio_unitario'], $order, $provider);

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
        $centroCosto = $user->costCenters->first()->cost_center_id;

        // Cambiar la base de datos según el centro de costo del usuario logueado
        $this->changeDatabaseConnection($centroCosto);

        try {
            $existsIncrdx = DB::table('incrdx')
                ->where('INALMNID', $validatedData['store'])
                ->where('INPRODID', (int) $acmvoiprid)
                ->where('CNTDOCID', 'RCN')
                ->where('INCRDXDOC', (int) $validatedData['document_number1'])
                ->where('INCRDXLIN', $acmvoilin)
                ->exists();

            if ($existsIncrdx) {
                $affected = DB::table('incrdx')
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

                if ($affected) {
                    Log::info("Actualización en incrdx realizada con éxito para INPRODID {$acmvoiprid} y ACMVOILIN {$acmvoilin}");
                } else {
                    Log::error("Fallo al actualizar incrdx para INPRODID {$acmvoiprid} y ACMVOILIN {$acmvoilin}");
                }
            } else {
                $inserted = DB::table('incrdx')->insert([
                    'INALMNID' => substr($validatedData['store'], 0, 15),
                    'INPRODID' => (int) $acmvoiprid,
                    'INLOTEID' => ' ',
                    'CNTDOCID' => 'RCN',
                    'INCRDXDOC' => (int) $validatedData['document_number1'],
                    'INCRDXLIN' => $acmvoilin,
                    'INCRDXLIB' => 'NL',
                    'INCRDXMON' => 'MXP',
                    'INCRDXFTRN' => now()->format('Y-m-d H:i:s'),
                    'CNCIASID' => 1,
                    'INCRDXFCRN' => now()->format('Y-m-d H:i:s'),
                    'INCRDXFVEN' => '1753-01-01 00:00:00.000',
                    'INCRDXDOT' => 'OL1',
                    'INCRDXDON' => (int) $order->ACMVOIDOC,
                    'CNCDIRID' => (int) $provider->CNCDIRID,
                    'INCRDXCU' => (float) $costoUnitario,
                    'INCRDXQTY' => (float) $cantidadRecibida,
                    'INCRDXCUNT' => (float) $costoUnitario,
                    'INCRDXVAL' => (float) $costoTotal,
                    'INCRDXVANT' => (float) $costoTotal,
                    'INCRDXUMB' => substr((string) $unidadMedida, 0, 3),
                    'INCRDXUMT' => substr((string) $unidadMedida, 0, 3),
                    'INCRDXPOST' => 'N',
                    'INCRDXEXP' => 'RECEPCION DE MATERIAL',
                    'INCRDXSEO' => 1,
                    'INCRDXUFC' => '1753-01-01 00:00:00.000',
                    'INCRDXUHC' => now()->format('H:i:s'),
                    'INCRDXUSU' => substr(Auth::user()->name, 0, 10),
                    'INCRDXUFU' => now()->format('Y-m-d H:i:s'),
                    'INCRDXUHU' => now()->format('H:i:s'),
                    'INCRDXFUF' => '1753-01-01 00:00:00.000',
                    'ACRCOICD01ID' => 'REQ',
                ]);

                if ($inserted) {
                    Log::info("Inserción en incrdx realizada con éxito para INPRODID {$acmvoiprid} y ACMVOILIN {$acmvoilin}");
                } else {
                    Log::error("Fallo al insertar en incrdx para INPRODID {$acmvoiprid} y ACMVOILIN {$acmvoilin}");
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Error al insertar o actualizar en incrdx: " . $e->getMessage());
            throw $e;
        }
    }




    public function insertAcmroi($validatedData, $partida, $cantidadRecibida, $costoTotal, $costoUnitario, $order, $provider, $usuario, $fechaActual, $horaActual)
    {
        $user = Auth::user();
        $centroCosto = $user->costCenters->first()->cost_center_id;

        // Cambiar la base de datos según el centro de costo del usuario logueado
        $this->changeDatabaseConnection($centroCosto);

        try {
            $acmroilin = isset($partida['ACMVOILIN']) ? (int) $partida['ACMVOILIN'] : 0;
            if ($acmroilin === 0) {
                throw new \Exception("El valor de ACMVOILIN es nulo o cero para la partida con ID {$partida['ACMVOIPRID']}");
            }

            $producto = DB::connection('centro')->table('inprod')
                ->where('INPRODID', (int) $partida['ACMVOIPRID'])
                ->first();

            if (!$producto) {
                throw new \Exception("Producto con ID {$partida['ACMVOIPRID']} no encontrado.");
            }

            if ($costoUnitario <= 0) {
                Log::error("Costo unitario inválido para el producto ID {$partida['ACMVOIPRID']} en la línea {$acmroilin}");
                throw new \Exception("Costo unitario inválido: {$costoUnitario} para el producto ID {$partida['ACMVOIPRID']}");
            }

            // Asegurar que los valores numéricos estén formateados correctamente como decimales
            $costoTotal = number_format((float) $costoTotal, 6, '.', '');
            $costoUnitario = number_format((float) $costoUnitario, 6, '.', '');
            $acmroiVolu = number_format((float) ($producto->INPRODVOL * $cantidadRecibida), 6, '.', '');
            $acmroiPesou = number_format((float) ($producto->INPRODPESO * $cantidadRecibida), 6, '.', '');

            // Obtener valores de ACMVOIFDOC y ACMVOIFDO2 desde acmvor1
            $ACMVOIFDOC = $order->ACMVOIFDOC ?? null;
            $ACMVOIFDO2 = DB::connection('centro')->table('acmvor1')
                ->where('ACMVOIDOC', $order->ACMVOIDOC)
                ->value('ACMVOIFDO2');

            // Fecha de recepción de la pantalla
            $reception_date = $validatedData['reception_date'] ?? now()->toDateString();

            // Datos a insertar en acmroi
            $insertData = [
                'CNCIASID' => 1,
                'ACMROITDOC' => 'RCN',
                'ACMROINDOC' => isset($validatedData['document_number1']) ? (int) $validatedData['document_number1'] : 0,
                'CNTDOCID' => 'OL1',
                'ACMROIDOC' => isset($order->ACMVOIDOC) ? (int) $order->ACMVOIDOC : 0,
                'ACMROILIN' => $acmroilin,
                'ACMROIFREC' => $reception_date,  // Fecha de recepción de la pantalla
                'CNCDIRID' => isset($provider->CNCDIRID) ? (int) $provider->CNCDIRID : 0,
                'INALMNID' => isset($validatedData['store']) ? substr($validatedData['store'], 0, 15) : '',
                'ACMVOIAOD' => isset($order->ACMVOIAOD) ? substr($order->ACMVOIAOD, 0, 3) : '',
                'CNCMNMID' => isset($order->CNCMNMID) ? substr($order->CNCMNMID, 0, 3) : '',
                'ACMROIFDOC' => $reception_date,  // Guardar la fecha de recepción en ACMROIFDOC
                'ACMROIUSRC' => '          ',
                'ACMROIFCEP' => $reception_date,  // Guardar la fecha de recepción en ACMROIFCEP
                'ACMROIFREQ' => $reception_date,  // Guardar la fecha de recepción en ACMROIFREQ
                'ACMROIFTRN' => $reception_date,  // Fecha de recepción de la pantalla
                'ACMROIFCNT' => $reception_date,  // Fecha de recepción de la pantalla
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
                'ACRCOICD01ID' => 'REQ',
                'ACMROICAN' => ' ',
                'CGUNNGID' => isset($order->CGUNNGID) ? substr(trim($order->CGUNNGID), 0, 15) : '',
                'ACMROIFGPT' => '1753-01-01 00:00:00.000',
                'ACMROIFENT' => $fechaActual->format('Y-m-d H:i:s'),
                'ACMROIFSAL' => $fechaActual->format('Y-m-d H:i:s'),
                'ACMROIFECC' => '1753-01-01 00:00:00.000',
                'ACMROIACCT' => 1,
                'ACMROIFOC' => $ACMVOIFDO2,  // Fecha OL desde acmvor1
                'ACMROICXP' => 'N',
                'ACMROIFDC' => $reception_date,  // Guardar la fecha de recepción en ACMROIFDC
                'ACMROIFVN' => '1753-01-01 00:00:00.000',
                'ACMROING2' => $costoTotal,
                'ACMROINI2' => $costoTotal * 0.16,
                'ACMROINM2' => $costoTotal * 1.16,
                'ACMROIVOLU' => $acmroiVolu,
                'ACMROIPESOU' => $acmroiPesou,
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

            ];

            Log::info('Datos que se insertarán en acmroi:', $insertData);

            $inserted = DB::connection('centro')->table('acmroi')->insert($insertData);

            if ($inserted) {
                Log::info("Nueva entrada en acmroi insertada correctamente.");
            } else {
                Log::error("Fallo al insertar en acmroi para INPRODID {$partida['ACMVOIPRID']} y ACMVOILIN {$acmroilin}");
            }
        } catch (\Exception $e) {
            Log::error("Error al insertar en acmroi: " . $e->getMessage());
            throw $e;
        }
    }






    public function updateInsdos($storeId, $productId, $cantidadRecibida, $costoTotal)
    {
        $user = Auth::user();
        $centroCosto = $user->costCenters->first()->cost_center_id;

        // Cambiar la base de datos según el centro de costo del usuario logueado
        $this->changeDatabaseConnection($centroCosto);

        try {
            $existingRecord = DB::connection('centro')->table('insdos')
                ->where('INALMNID', $storeId)
                ->where('INPRODID', $productId)
                ->first();

            if ($existingRecord) {
                $updated = DB::connection('centro')->table('insdos')
                    ->where('INALMNID', $storeId)
                    ->where('INPRODID', $productId)
                    ->update([
                        'INSDOSQDS' => DB::raw('INSDOSQDS + ' . (float) $cantidadRecibida),
                        'INSDOSVAL' => DB::raw('INSDOSVAL + ' . (float) $costoTotal),
                    ]);

                if ($updated) {
                    Log::info("Inventario actualizado para el producto ID {$productId} en el almacén {$storeId}");
                } else {
                    Log::error("Fallo al actualizar inventario para el producto ID {$productId} en el almacén {$storeId}");
                }
            } else {
                $inserted = DB::connection('centro')->table('insdos')->insert([
                    'INALMNID' => $storeId,
                    'INPRODID' => $productId,
                    'INSDOSQDS' => $cantidadRecibida,
                    'INSDOSVAL' => $costoTotal,
                ]);

                if ($inserted) {
                    Log::info("Nuevo inventario insertado para el producto ID {$productId} en el almacén {$storeId}");
                } else {
                    Log::error("Fallo al insertar nuevo inventario para el producto ID {$productId} en el almacén {$storeId}");
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Error al actualizar insdos: " . $e->getMessage());
            throw $e;
        }
    }

    private function changeDatabaseConnection($centroCosto)
    {
        $centroCosto = trim($centroCosto); // Eliminar espacios en blanco

        // Revisar y establecer la configuración adecuada según el centro de costo
        switch ($centroCosto) {
            case 'FD09':
                config()->set('database.connections.centro', [
                    'driver' => 'sqlsrv',
                    'host' => '128.76.8.245',
                    'port' => '1433',
                    'database' => 'ERP_TBI_TEC_PRO_FDGO',
                    'username' => 'programacion',
                    'password' => 'G3npr',
                    'charset' => 'utf8',
                    'prefix' => '',
                ]);
                break;

            case 'FD10':
                config()->set('database.connections.centro', [
                    'driver' => 'sqlsrv',
                    'host' => '128.76.8.6',
                    'port' => '1433',
                    'database' => 'ERP_TBI_TEC_PRO_FDGO',
                    'username' => 'USRTEST',
                    'password' => 'PROTOTIPO',
                    'charset' => 'utf8',
                    'prefix' => '',
                ]);
                break;

            case 'FD04':
                config()->set('database.connections.centro', [
                    'driver' => 'sqlsrv',
                    'host' => '148.76.8.148',
                    'port' => '1433',
                    'database' => 'ERP_TBI_TEC_PRO_FDGO',
                    'username' => 'USRTEST',
                    'password' => 'PROTOTIPO',
                    'charset' => 'utf8',
                    'prefix' => '',
                ]);
                break;

            default:
                Log::error("La configuración de la base de datos está incompleta para el centro de costo: $centroCosto. Datos presentes: " . json_encode(config('database.connections.centro')));
                throw new \Exception("La configuración de la base de datos está incompleta para el centro de costo: $centroCosto.");
        }

        // Limpiar y establecer la conexión
        DB::purge('centro');
        DB::reconnect('centro');
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
}
