<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Providers;
use App\Models\Receptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // Importa la clase Log

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
        $user = Auth::user(); // Obtener el usuario autenticado

        if (!$user) {
            // Manejo de error si el usuario no está autenticado
            return redirect()->route('login');
        }

        // Obtener los IDs de los centros de costos asociados al usuario
        $centrosCostosIds = $user->costCenters->pluck('cost_center_id')->toArray();

        $query = Order::query();

        // Filtrar por centros de costos asociados al usuario
        if (!empty($centrosCostosIds)) {
            $query->whereIn('ACMVOIALID', $centrosCostosIds);
        }

        // Filtrar registros dentro de los últimos 6 meses
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        $query->where('ACMVOIFDOC', '>=', $sixMonthsAgo);

        // Aplicar filtros adicionales
        if ($request->filled('ACMVOIDOC')) {
            $query->where('ACMVOIDOC', $request->input('ACMVOIDOC'));
        }

        if ($request->filled('CNCDIRID')) {
            $query->where('CNCDIRID', $request->input('CNCDIRID'));
        }

        if ($request->filled('CNCDIRNOM')) {
            $query->whereHas('provider', function ($q) use ($request) {
                $q->where('CNCDIRNOM', 'like', '%' . $request->input('CNCDIRNOM') . '%');
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('ACMVOIFDOC', [$request->input('start_date'), $request->input('end_date')]);
        }

        // Filtrar órdenes que tienen partidas válidas en la tabla `acmvor1`
        $query->whereExists(function ($subquery) {
            $subquery->select(DB::raw(1))
                ->from('acmvor1')
                ->leftJoin('acmroi', function ($join) {
                    $join->on('acmvor1.ACMVOIDOC', '=', 'acmroi.ACMROIDOC')
                        ->on('acmvor1.ACMVOILIN', '=', 'acmroi.ACMROILIN');
                })
                ->whereColumn('acmvor1.ACMVOIDOC', 'ACMVOR.ACMVOIDOC')
                ->where(function ($query) {
                    $query->whereNull('acmroi.ACACTLID')
                        ->orWhere('acmroi.ACACTLID', '!=', 'CANCELADO');
                })
                ->whereNull('acmroi.ACACTLID'); // Excluir partidas que están en `acmroi`
        });

        // Aplicar ordenamiento solo para columnas válidas
        $sortableColumns = ['CNTDOCID', 'ACMVOIDOC', 'CNCDIRID', 'ACMVOIFDOC', 'ACMVOIALID'];
        $sortColumn = $request->input('sortColumn', 'ACMVOIDOC');
        $sortDirection = $request->input('sortDirection', 'desc');

        if (in_array($sortColumn, $sortableColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('ACMVOIDOC', 'desc'); // Valor por defecto en caso de columnas inválidas
        }

        // Obtener las órdenes paginadas
        $orders = $query->paginate(10);

        // Depuración
        // dd($orders);

        if ($request->ajax()) {
            return view('orders_table', compact('orders', 'sortColumn', 'sortDirection'))->render();
        }

        return view('orders', compact('orders', 'sortColumn', 'sortDirection'));
    }

    public function autocomplete(Request $request)
    {
        $query = $request->input('query');
        $field = $request->input('field');

        $results = DB::table('CNCDIR')
            ->where($field, 'LIKE', "%{$query}%")
            ->get(['CNCDIRID', 'CNCDIRNOM']);

        return response()->json($results);
    }

    public function showReceptions($ACMVOIDOC)
    {
        $order = Order::where('ACMVOIDOC', $ACMVOIDOC)
            ->with('provider')
            ->first();

        $receptions = Receptions::where('ACMVOIDOC', $ACMVOIDOC)->get();
        $provider = Providers::where('CNCDIRID', $order->CNCDIRID)->first();

        $cntdoc = DB::table('cntdoc')
            ->where('cntdocid', 'RCN')
            ->first();

        if ($cntdoc && isset($cntdoc->CNTDOCNSIG)) {
            $num_rcn_letras = $cntdoc->CNTDOCNSIG;

            if (is_numeric($num_rcn_letras)) {
                $new_value = intval($num_rcn_letras) + 1;
            } else {
                $new_value = chr(ord($num_rcn_letras) + 1);
            }

            DB::table('cntdoc')
                ->where('cntdocid', 'RCN')
                ->update(['CNTDOCNSIG' => $new_value]);
        } else {
            $num_rcn_letras = 'NUMERO';
        }

        $currentDate = now()->toDateString();

        // Excluir las partidas que ya están en la tabla `acmroi`
        $partidas = DB::table('acmvor1')
            ->leftJoin('acmroi', function ($join) {
                $join->on('acmvor1.ACMVOIDOC', '=', 'acmroi.ACMROIDOC')
                    ->on('acmvor1.ACMVOILIN', '=', 'acmroi.ACMROILIN');
            })
            ->where('acmvor1.ACMVOIDOC', $ACMVOIDOC)
            ->where(function ($query) {
                $query->whereNull('acmroi.ACACTLID')
                    ->orWhere('acmroi.ACACTLID', '!=', 'CANCELADO');
            })
            ->whereNull('acmroi.ACACTLID')  // Excluir partidas que están en `acmroi`
            ->select('acmvor1.ACMVOILIN', 'acmvor1.ACMVOIPRID', 'acmvor1.ACMVOIPRDS', 'acmvor1.ACMVOINPAR', 'acmvor1.ACMVOIUMT', 'acmvor1.ACMVOIQTO', 'acmvor1.ACMVOINPO', 'acmvor1.ACMVOIIVA')
            ->get();

        return view('receptions', compact('receptions', 'order', 'provider', 'num_rcn_letras', 'currentDate', 'partidas'));
    }

    public function receiptOrder(Request $request, $ACMVOIDOC)
    {
        $order = Order::where('ACMVOIDOC', $ACMVOIDOC)->first();
        $provider = Providers::where('CNCDIRID', $order->CNCDIRID)->first();
    
        if (!$order || !$provider) {
            return redirect()->route('orders')->with('error', 'Orden o proveedor no encontrado.');
        }
    
        // Validación de todos los campos del formulario excepto flete
        $validatedData = $request->validate([
            'carrier_number' => 'required|string',
            'carrier_name' => 'required|string',
            'document_type' => 'required|string',
            'document_number' => 'required|string',
            'supplier_name' => 'required|string',
            'reference_type' => 'required|string',
            'store' => 'required|string',
            'reference' => 'required|string',
            'reception_date' => 'required|date',
            'document_type1' => 'required|string',
            'document_number1' => 'required|string',
            'total_cost' => 'required|numeric',
            'cantidad_recibida.*' => 'required|numeric|min:0',
            'precio_unitario.*' => 'required|numeric|min:0',
        ]);
    
        // Añadir validación del flete si está seleccionado
        if ($request->input('flete_select') == 1) {
            $request->validate([
                'freight' => 'required|string',
            ]);
    
            // Eliminar comas del valor de freight y convertir a float
            $validatedData['freight'] = (float)str_replace(',', '', $request->input('freight'));
        } else {
            $validatedData['freight'] = 0.0;
        }
    
        // Lógica de inserción en la tabla principal
        if ($validatedData['freight'] > 0) {
            $this->insertFreight($validatedData, $provider);
        }
    
        // Lógica de inserción por partidas
        $this->insertPartidas($validatedData, $request->input('cantidad_recibida'), $request->input('precio_unitario'), $order, $provider);
    
        // Lógica para afectar los campos en insdos
    
        // Redirección después de procesar los datos
        return redirect()->route('orders')->with('success', 'Recepción registrada correctamente.');
    }
    


    
    private function insertFreight($validatedData, $provider)
{
    // Inserción de todos los campos en la tabla Freights
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
}
                    
                    private function insertPartidas($validatedData, $cantidadesRecibidas, $preciosUnitarios, $order, $provider)
{
    $fechaActual = now();
    $horaActual = now()->format('H:i:s');
    $usuario = Auth::user()->name;

    // Obtener partidas del pedido
    $partidas = DB::table('acmvor1')
        ->where('ACMVOIDOC', $order->ACMVOIDOC)
        ->get();

    foreach ($cantidadesRecibidas as $index => $cantidadRecibida) {
        if (isset($partidas[$index])) {
            $partida = $partidas[$index];
            $costoUnitario = (float) $preciosUnitarios[$index];
            $cantidadRecibida = (float) $cantidadRecibida; // Convertir a decimal
            $costoTotal = $cantidadRecibida * $costoUnitario;

            // Obtener los datos de INPROD
            $producto = DB::table('inprod')
                ->where('INPRODID', (int) $partida->ACMVOIPRID)
                ->first();

            if (!$producto) {
                throw new \Exception("Producto con ID {$partida->ACMVOIPRID} no encontrado en la tabla inprod.");
            }

            // Verificar si el registro ya existe en incrdx
            $exists = DB::table('incrdx')
                ->where('INALMNID', $validatedData['store'])
                ->where('INPRODID', (int) $partida->ACMVOIPRID)
                ->where('CNTDOCID', 'RCN')
                ->where('INCRDXDOC', (int) $validatedData['document_number1'])
                ->where('INCRDXLIN', $index + 1)
                ->exists();

            if ($exists) {
                // Si el registro existe, puedes decidir actualizarlo o ignorar la inserción
                Log::info("Registro ya existe en incrdx: Store {$validatedData['store']}, Product ID {$partida->ACMVOIPRID}, Document Number {$validatedData['document_number1']}, Line " . ($index + 1));
            } else {
                // Si el registro no existe, insertarlo
                DB::table('incrdx')->insert([
                    'INALMNID' => substr($validatedData['store'], 0, 15), // char(15)
                    'INPRODID' => (int) $partida->ACMVOIPRID, // decimal(10,0)
                    'INLOTEID' => ' ', // char(30) (valor en blanco)
                    'CNTDOCID' => 'RCN', // char(3)
                    'INCRDXDOC' => (int) $validatedData['document_number1'], // decimal(10,0)
                    'INCRDXLIN' => $index + 1, // smallmoney
                    'INCRDXLIB' => 'NL', // char(2)
                    'INCRDXMON' => 'MXP', // char(3)
                    'INCRDXFTRN' => $fechaActual->format('Y-m-d H:i:s'), // datetime
                    'CNCIASID' => 1, // decimal(10,0)
                    'INCRDXFCRN' => $fechaActual->format('Y-m-d H:i:s'), // datetime
                    'INCRDXFVEN' => '1753-01-01 00:00:00.000', // datetime
                    'INCRDXDOT' => 'OL1', // char(3)
                    'INCRDXDON' => (int) $partida->ACMVOIDOC, // decimal(10,0)
                    'CNCDIRID' => (int) $provider->CNCDIRID, // decimal(10,0)
                    'INCRDXCU' => (float) $costoUnitario, // decimal(17,6)
                    'INCRDXQTY' => (float) $cantidadRecibida, // decimal(16,4)
                    'INCRDXCUNT' => (float) $costoUnitario, // decimal(17,6)
                    'INCRDXVAL' => (float) $costoTotal, // decimal(17,6)
                    'INCRDXVANT' => (float) $costoTotal, // decimal(17,6)
                    'INCRDXUMB' => substr((string) $partida->ACMVOIUMT, 0, 3), // char(3)
                    'INCRDXUMT' => substr((string) $partida->ACMVOIUMT, 0, 3), // char(3)
                    'INCRDXPOST' => 'N', // char(1)
                    'INCRDXEXP' => 'RECEPCION DE MATERIAL', // char(50)
                    'INCRDXSEO' => 1, // decimal(10,0)
                    'INCRDXUFC' => '1753-01-01 00:00:00.000', // datetime
                    'INCRDXUHC' => $horaActual, // datetime
                    'INCRDXUSU' => substr($usuario, 0, 10), // char(10)
                    'INCRDXUFU' => $fechaActual->format('Y-m-d H:i:s'), // datetime
                    'INCRDXUHU' => $horaActual, // datetime
                    'INCRDXFUF' => '1753-01-01 00:00:00.000', // datetime
                    'ACRCOICD01ID' => 'REQ', // char(10)
                ]);
                Log::info("Nueva entrada en incrdx insertada: " . json_encode([
                    'INALMNID' => $validatedData['store'],
                    'INPRODID' => (int) $partida->ACMVOIPRID,
                    'CNTDOCID' => 'RCN',
                    'INCRDXDOC' => (int) $validatedData['document_number1'],
                    'INCRDXLIN' => $index + 1,
                    // Otros campos omitidos para brevedad
                ]));
            }

            // Actualizar insdos
            $insdos = DB::table('insdos')
                ->where('INALMNID', $validatedData['store'])
                ->where('INPRODID', (int) $partida->ACMVOIPRID)
                ->first();

            if ($insdos) {
                // Actualizar los valores existentes
                $nuevoINSDOSVAL = $insdos->INSDOSVAL + $costoTotal;
                $nuevoINSDOSQDS = $insdos->INSDOSQDS + $cantidadRecibida;

                DB::table('insdos')
                    ->where('INALMNID', $validatedData['store'])
                    ->where('INPRODID', (int) $partida->ACMVOIPRID)
                    ->update([
                        'INSDOSVAL' => $nuevoINSDOSVAL,
                        'INSDOSVANT' => $insdos->INSDOSVAL, // Guardar el valor anterior
                        'INSDOSQDS' => $nuevoINSDOSQDS,
                        'INSDOSQCT' => $insdos->INSDOSQCT, // Mantener el valor existente
                    ]);
                Log::info("Actualización de insdos realizada: " . json_encode([
                    'INSDOSVAL' => $nuevoINSDOSVAL,
                    'INSDOSVANT' => $insdos->INSDOSVAL,
                    'INSDOSQDS' => $nuevoINSDOSQDS,
                    'INSDOSQCT' => $insdos->INSDOSQCT,
                ]));
            } else {
                // Insertar nuevos valores
                DB::table('insdos')->insert([
                    'INALMNID' => $validatedData['store'],
                    'INPRODID' => (int) $partida->ACMVOIPRID,
                    'INSDOSVAL' => $costoTotal,
                    'INSDOSVANT' => 0.0, // No hay valor anterior
                    'INSDOSQDS' => $cantidadRecibida,
                    'INSDOSQCT' => 0.0, // No hay cantidad en tránsito
                ]);
                Log::info("Nueva entrada en insdos insertada: " . json_encode([
                    'INALMNID' => $validatedData['store'],
                    'INPRODID' => (int) $partida->ACMVOIPRID,
                    'INSDOSVAL' => $costoTotal,
                    'INSDOSVANT' => 0.0,
                    'INSDOSQDS' => $cantidadRecibida,
                    'INSDOSQCT' => 0.0,
                ]));
            }

            // Insertar datos en la tabla acmroi
            DB::table('acmroi')->insert([
                'CNCIASID' => 1, // decimal(10,0)
                'ACMROITDOC' => substr('OL1', 0, 3), // char(3)
                'ACMROINDOC' => (int) $order->ACMVOIDOC, // decimal(10,0)
                'CNTDOCID' => substr('OL1', 0, 3), // char(3)
                'ACMROIDOC' => (int) $validatedData['document_number1'], // decimal(10,0)
                'ACMROILIN' => $index + 1, // smallmoney
                'ACMROIFREC' => $order->ACMVOIFREC, // datetime
                'CNCDIRID' => (int) $provider->CNCDIRID, // decimal(10,0)
                'INALMNID' => substr($validatedData['store'], 0, 15), // char(15)
                'ACMVOIAOD' => substr($order->ACMVOIAOD, 0, 3), // char(3)
                'CNCMNMID' => substr($order->CNCMNMID, 0, 3), // char(3)
                'ACMROIFDOC' => $order->ACMROIFDOC, // datetime
                'ACMROIUSRC'=> 'USUARIOPRUEBAS',
                'ACMROIFCEP' => $order->ACMROIFCEP, // datetime
                'ACMROIFREQ' => $order->ACMROIFREQ, // datetime
                'ACMROIFTRN' => $order->ACMROIFTRN, // datetime
                'ACMROIFCNT' => $order->ACMROIFCNT, // datetime
                'ACMVOIPR' => $order->ACMVOIPR, // smallint
                'INPRODID' => (int) $partida->ACMVOIPRID, // decimal(10,0)
                'ACMROIDSC' => substr($partida->ACMVOIPRDS, 0, 60), // char(60)
                'ACMROIUMT' => substr($partida->ACMVOIUMT, 0, 3), // char(3)
                'ACMROIIVA' => $partida->ACMVOIIVA, // smallmoney
                'ACMROIQT' => $cantidadRecibida, // decimal(16,4)
                'ACMROIQTTR' => $cantidadRecibida, // decimal(17,6)
                'ACMROINP' => $costoUnitario, // decimal(17,6)
                'ACMROINM' => $costoTotal, // decimal(17,6)
                'ACMROINI' => $costoTotal * 1.16, // decimal(17,6), ajustar IVA si corresponde
                'ACMROING' => $costoTotal, // decimal(17,6)
                'ACMROIDOC2' => (int) $validatedData['document_number1'], // decimal(10,0)
                'ACMROIDOI2' => substr('RCN', 0, 3), // char(3)
                'ACMROITDOCCAN'=>1,
                'ACMROINDOCCAN'=>1,
                'ACMROIDOI3' => substr('PC', 0, 3), // char(3)
                'ACMROIDOC3' => $this->getNewCNTDOCNSIG(), // decimal(10,0), obtener nuevo valor de CNTDOCNSIG
                'ACMROIREF' => substr($validatedData['reference'], 0, 60), // char(60)
                'ACMROITREF' => (int) $validatedData['reference_type'], // smallint
                'ACRCOICD01ID' => substr('REQ', 0, 10), // char(10)
                'ACMROIUSRC' => substr($usuario, 0, 10), // char(10)
                'ACMROICAN' => substr(' ', 0, 1), // char(1)
                'CGUNNGID' => substr($order->CGUNNGID, 0, 15), // char(15)
                'ACMROIFGPT' => '1753-01-01 00:00:00.000', // datetime
                'ACMROIFENT' => $fechaActual->format('Y-m-d H:i:s'), // datetime
                'ACMROIFSAL' => $fechaActual->format('Y-m-d H:i:s'), // datetime
                'ACMROIFECC' => '1753-01-01 00:00:00.000', // datetime
                'ACMROIVOLU' => $producto->INPRODVOL * $cantidadRecibida, // decimal(14,7)
                'ACMROIPESOU' => $producto->INPRODPESO * $cantidadRecibida, // decimal(14,7)
                'ACMROIVOLT' => $producto->INPRODVOL * $cantidadRecibida, // decimal(14,7)
                'ACMROIPESOT' => $producto->INPRODPESO * $cantidadRecibida, // decimal(14,7)
            ]);
        } else {
            // Manejar el caso donde la partida no existe
            throw new \Exception("La partida en la posición {$index} no existe.");
        }
    }
}

private function getNewCNTDOCNSIG()
{
    $cntdoc = DB::table('cntdoc')
        ->where('cntdocid', 'PC')
        ->first();

    if ($cntdoc && isset($cntdoc->CNTDOCNSIG)) {
        $num_rcn_letras = $cntdoc->CNTDOCNSIG;

        if (is_numeric($num_rcn_letras)) {
            $new_value = intval($num_rcn_letras) + 1;
        } else {
            $new_value = chr(ord($num_rcn_letras) + 1);
        }

        DB::table('cntdoc')
            ->where('cntdocid', 'PC')
            ->update(['CNTDOCNSIG' => $new_value]);

        return $new_value;
    } else {
        return 'NUMERO';
    }
}

    
}


