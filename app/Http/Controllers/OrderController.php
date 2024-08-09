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

        $sixMonthsAgo = Carbon::now()->subMonths(6);
        $query->where('ACMVOIFDOC', '>=', $sixMonthsAgo);

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

        $query->whereExists(function ($subquery) {
            $subquery->select(DB::raw(1))
                ->from('acmvor1')
                ->leftJoin('acmroi', function ($join) {
                    $join->on('acmvor1.ACMVOIDOC', '=', 'acmroi.ACMROIDOC')
                        ->on('acmvor1.ACMVOIALM', '=', 'acmroi.INALMNID')
                        ->on('acmvor1.ACMVOIPRID', '=', 'acmroi.INPRODID');
                })
                ->whereColumn('acmvor1.ACMVOIDOC', 'ACMVOR.ACMVOIDOC')
                ->where(function ($query) {
                    $query->whereNull('acmroi.ACMROIDOC')
                        ->orWhere('acmroi.ACACTLID', '!=', 'CANCELADO');
                });
        });

        $sortableColumns = ['CNTDOCID', 'ACMVOIDOC', 'CNCDIRID', 'ACMVOIFDOC', 'ACMVOIALID'];
        $sortColumn = $request->input('sortColumn', 'ACMVOIDOC');
        $sortDirection = $request->input('sortDirection', 'desc');

        if (in_array($sortColumn, $sortableColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('ACMVOIDOC', 'desc');
        }

        $orders = $query->paginate(10);

        if ($request->ajax()) {
            return view('orders_table', compact('orders', 'sortColumn', 'sortDirection'))->render();
        }

        return view('orders', compact('orders', 'sortColumn', 'sortDirection'));
    }

    public function showReceptions($ACMVOIDOC)
    {
        $order = Order::where('ACMVOIDOC', $ACMVOIDOC)
            ->with('provider')
            ->first();

        if (!$order) {
            return redirect()->route('orders')->with('error', 'Orden no encontrada.');
        }

        $receptions = DB::table('ACMVOR1')->where('ACMVOIDOC', $ACMVOIDOC)->get();
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

        $partidas = DB::table('acmvor1')
            ->leftJoin('acmroi', function ($join) {
                $join->on('acmvor1.ACMVOIDOC', '=', 'acmroi.ACMROIDOC')
                    ->on('acmvor1.ACMVOIALM', '=', 'acmroi.INALMNID')
                    ->on('acmvor1.ACMVOIPRID', '=', 'acmroi.INPRODID');
            })
            ->where('acmvor1.ACMVOIDOC', $ACMVOIDOC)
            ->where(function ($query) {
                $query->whereNull('acmroi.ACMROIDOC')
                    ->orWhere('acmroi.ACACTLID', '!=', 'CANCELADO');
            })
            ->select('acmvor1.ACMVOILIN', 'acmvor1.ACMVOIPRID', 'acmvor1.ACMVOIPRDS', 'acmvor1.ACMVOINPAR', 'acmvor1.ACMVOIUMT', 'acmvor1.ACMVOIQTO', 'acmvor1.ACMVOINPO', 'acmvor1.ACMVOIIVA')
            ->get();

        if ($partidas->isEmpty()) {
            return redirect()->route('orders')->with('error', 'No hay partidas válidas para esta recepción.');
        }

        return view('receptions', compact('receptions', 'order', 'provider', 'num_rcn_letras', 'currentDate', 'partidas'));
    }

    public function receiptOrder(Request $request, $ACMVOIDOC)
    {
        $order = Order::where('ACMVOIDOC', $ACMVOIDOC)->first();
        $provider = Providers::where('CNCDIRID', $order->CNCDIRID)->first();

        if (!$order || !$provider) {
            return response()->json(['success' => false, 'message' => 'Orden o proveedor no encontrado.']);
        }

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

        DB::beginTransaction();

        try {
            $incrdxInserted = $this->insertPartidas($validatedData, $request->input('cantidad_recibida'), $request->input('precio_unitario'), $order, $provider);

            if (!$incrdxInserted) {
                throw new \Exception('Error al insertar las partidas.');
            }

            if ($request->input('flete_select') == 1) {
                $freightInserted = $this->insertFreight($validatedData, $provider);
                if (!$freightInserted) {
                    throw new \Exception('Error al insertar el flete. Inserciones revertidas.');
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Recepción registrada con éxito.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en la recepción de la orden: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function insertPartidas($validatedData, $cantidadesRecibidas, $preciosUnitarios, $order, $provider)
    {
        $fechaActual = now();
        $horaActual = now()->format('H:i:s');
        $usuario = Auth::user()->name ?? 'Sistema';

        $partidas = DB::table('acmvor1')->where('ACMVOIDOC', $order->ACMVOIDOC)->get();

        try {
            foreach ($cantidadesRecibidas as $index => $cantidadRecibida) {
                if ($cantidadRecibida > 0 && isset($partidas[$index])) {
                    $partida = $partidas[$index];
                    $costoUnitario = isset($preciosUnitarios[$index]) && $preciosUnitarios[$index] > 0 ? (float) $preciosUnitarios[$index] : null;

                    if ($costoUnitario === null) {
                        Log::error("Costo unitario no ingresado o inválido para la partida con ID {$partida->ACMVOIPRID}, se omite la recepción.");
                        continue;
                    }

                    $costoTotal = $cantidadRecibida * $costoUnitario;

                    $inserted = $this->insertOrUpdateIncrdx($validatedData, $partida, $index, $cantidadRecibida, $costoTotal, $costoUnitario, $provider, $order);

                    if (!$inserted) {
                        throw new \Exception("Error al insertar o actualizar en incrdx para la partida con ID {$partida->ACMVOIPRID}");
                    }

                    $this->updateInsdos($validatedData['store'], $partida, $cantidadRecibida, $costoTotal);

                    $this->insertAcmroi($validatedData, $partida, $cantidadRecibida, $costoTotal, $costoUnitario, $order, $provider, $usuario, $fechaActual, $horaActual);
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Error al procesar la recepción de la orden: " . $e->getMessage());
            throw $e;
        }
    }

    public function insertFreight($validatedData, $provider)
    {
        try {
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
            return true;
        } catch (\Exception $e) {
            Log::error("Error al insertar freight: " . $e->getMessage());
            throw $e;
        }
    }

    public function insertOrUpdateIncrdx($validatedData, $partida, $index, $cantidadRecibida, $costoTotal, $costoUnitario, $provider, $order)
    {
        try {
            $existsIncrdx = DB::table('incrdx')
                ->where('INALMNID', $validatedData['store'])
                ->where('INPRODID', (int) $partida->ACMVOIPRID)
                ->where('CNTDOCID', 'RCN')
                ->where('INCRDXDOC', (int) $validatedData['document_number1'])
                ->where('INCRDXLIN', $index + 1)
                ->exists();

            if ($existsIncrdx) {
                DB::table('incrdx')
                    ->where('INALMNID', $validatedData['store'])
                    ->where('INPRODID', (int) $partida->ACMVOIPRID)
                    ->where('CNTDOCID', 'RCN')
                    ->where('INCRDXDOC', (int) $validatedData['document_number1'])
                    ->where('INCRDXLIN', $index + 1)
                    ->update([
                        'INCRDXQTY' => (float) $cantidadRecibida,
                        'INCRDXVAL' => (float) $costoTotal,
                        'INCRDXVANT' => (float) $costoTotal,
                    ]);

                Log::info("Actualización de incrdx: Store {$validatedData['store']}, Product ID {$partida->ACMVOIPRID}, Document Number {$validatedData['document_number1']}, Line " . ($index + 1));
            } else {
                DB::table('incrdx')->insert([
                    'INALMNID' => substr($validatedData['store'], 0, 15),
                    'INPRODID' => (int) $partida->ACMVOIPRID,
                    'INLOTEID' => ' ',
                    'CNTDOCID' => 'RCN',
                    'INCRDXDOC' => (int) $validatedData['document_number1'],
                    'INCRDXLIN' => $index + 1,
                    'INCRDXLIB' => 'NL',
                    'INCRDXMON' => 'MXP',
                    'INCRDXFTRN' => now()->format('Y-m-d H:i:s'),
                    'CNCIASID' => 1,
                    'INCRDXFCRN' => now()->format('Y-m-d H:i:s'),
                    'INCRDXFVEN' => '1753-01-01 00:00:00.000',
                    'INCRDXDOT' => 'OL1',
                    'INCRDXDON' => (int) $partida->ACMVOIDOC,
                    'CNCDIRID' => (int) $provider->CNCDIRID,
                    'INCRDXCU' => (float) $costoUnitario,
                    'INCRDXQTY' => (float) $cantidadRecibida,
                    'INCRDXCUNT' => (float) $costoUnitario,
                    'INCRDXVAL' => (float) $costoTotal,
                    'INCRDXVANT' => (float) $costoTotal,
                    'INCRDXUMB' => substr((string) $partida->ACMVOIUMT, 0, 3),
                    'INCRDXUMT' => substr((string) $partida->ACMVOIUMT, 0, 3),
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

                Log::info("Nueva entrada en incrdx insertada: " . json_encode([
                    'INALMNID' => $validatedData['store'],
                    'INPRODID' => (int) $partida->ACMVOIPRID,
                    'CNTDOCID' => 'RCN',
                    'INCRDXDOC' => (int) $validatedData['document_number1'],
                    'INCRDXLIN' => $index + 1,
                ]));
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Error al insertar o actualizar en incrdx: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateInsdos($store, $partida, $cantidadRecibida, $costoTotal)
    {
        try {
            $insdos = DB::table('insdos')
                ->where('INALMNID', $store)
                ->where('INPRODID', (int) $partida->ACMVOIPRID)
                ->first();

            if ($insdos) {
                $nuevoINSDOSVAL = $insdos->INSDOSVAL + $costoTotal;
                $nuevoINSDOSQDS = $insdos->INSDOSQDS + $cantidadRecibida;

                DB::table('insdos')
                    ->where('INALMNID', $store)
                    ->where('INPRODID', (int) $partida->ACMVOIPRID)
                    ->update([
                        'INSDOSVAL' => $nuevoINSDOSVAL,
                        'INSDOSVANT' => $insdos->INSDOSVAL,
                        'INSDOSQDS' => $nuevoINSDOSQDS,
                        'INSDOSQCT' => $insdos->INSDOSQCT,
                    ]);

                Log::info("Actualización de insdos realizada: " . json_encode([
                    'INSDOSVAL' => $nuevoINSDOSVAL,
                    'INSDOSVANT' => $insdos->INSDOSVAL,
                    'INSDOSQDS' => $nuevoINSDOSQDS,
                    'INSDOSQCT' => $insdos->INSDOSQCT,
                ]));
            } else {
                DB::table('insdos')->insert([
                    'INALMNID' => $store,
                    'INPRODID' => (int) $partida->ACMVOIPRID,
                    'INSDOSVAL' => $costoTotal,
                    'INSDOSVANT' => 0.0,
                    'INSDOSQDS' => $cantidadRecibida,
                    'INSDOSQCT' => 0.0,
                ]);

                Log::info("Nueva entrada en insdos insertada: " . json_encode([
                    'INALMNID' => $store,
                    'INPRODID' => (int) $partida->ACMVOIPRID,
                    'INSDOSVAL' => $costoTotal,
                    'INSDOSVANT' => 0.0,
                    'INSDOSQDS' => $cantidadRecibida,
                    'INSDOSQCT' => 0.0,
                ]));
            }
        } catch (\Exception $e) {
            Log::error("Error al actualizar insdos: " . $e->getMessage());
            throw $e;
        }
    }

    public function insertAcmroi($validatedData, $partida, $cantidadRecibida, $costoTotal, $costoUnitario, $order, $provider, $usuario, $fechaActual, $horaActual)
    {
        try {
            $acmroilin = DB::table('acmroi')
                ->where('ACMROIDOC', $order->ACMVOIDOC)
                ->max('ACMROILIN') + 1;
    
            $producto = DB::table('inprod')
                ->where('INPRODID', (int) $partida->ACMVOIPRID)
                ->first();
    
            if ($costoUnitario <= 0) {
                Log::error("Costo unitario inválido para el producto ID {$partida->ACMVOIPRID} en la línea {$acmroilin}");
                throw new \Exception("Costo unitario inválido: {$costoUnitario} para el producto ID {$partida->ACMVOIPRID}");
            }
    
            Log::info("Preparando inserción en acmroi para la orden {$order->ACMVOIDOC}", [
                'ACMROIDOC' => $order->ACMVOIDOC,
                'ACMROILIN' => $acmroilin,
                'INPRODID' => $partida->ACMVOIPRID,
                'ACMROIDSC' => $partida->ACMVOIPRDS,
                'ACMROIQT' => $cantidadRecibida,
                'ACMROINM' => $costoTotal,
                'Costo Unitario' => $costoUnitario
            ]);
    
            DB::table('acmroi')->insert([
                'CNCIASID' => 1,
                'ACMROITDOC' => 'RCN',
                'ACMROINDOC' => (int) $validatedData['document_number1'],
                'CNTDOCID' => 'OL1',
                'ACMROIDOC' => (int) $order->ACMVOIDOC,
                'ACMROILIN' => $acmroilin,
                'ACMROIFREC' => $order->ACMVOIFREC,
                'CNCDIRID' => (int) $provider->CNCDIRID,
                'INALMNID' => substr($validatedData['store'], 0, 15),
                'ACMVOIAOD' => substr($order->ACMVOIAOD, 0, 3),
                'CNCMNMID' => substr($order->CNCMNMID, 0, 3),
                'ACMROIFDOC' => $order->ACMROIFDOC,
                'ACMROIUSRC' => $usuario,
                'ACMROIFCEP' => $order->ACMROIFCEP,
                'ACMROIFREQ' => $order->ACMROIFREQ,
                'ACMROIFTRN' => $order->ACMROIFTRN,
                'ACMROIFCNT' => $order->ACMROIFCNT,
                'ACMVOIPR' => $order->ACMVOIPR,
                'INPRODID' => (int) $partida->ACMVOIPRID,
                'ACMROIDSC' => substr($partida->ACMVOIPRDS, 0, 60),
                'ACMROIUMT' => substr($partida->ACMVOIUMT, 0, 3),
                'ACMROIIVA' => $partida->ACMVOIIVA,
                'ACMROIQT' => $cantidadRecibida,
                'ACMROIQTTR' => $cantidadRecibida,
                'ACMROINP' => $costoUnitario,
                'ACMROINM' => $costoTotal,
                'ACMROINI' => $costoTotal * 1.16,
                'ACMROING' => $costoTotal,
                'ACMROIDOC2' => (int) $validatedData['document_number1'],
                'ACMROIDOI2' => 'RCN',
                'ACMROITDOCCAN' => 1,
                'ACMROINDOCCAN' => 1,
                'ACMROIDOI3' => 'PC',
                'ACMROIDOC3' => $this->getNewCNTDOCNSIG(),
                'ACMROIREF' => substr($validatedData['reference'], 0, 60),
                'ACMROITREF' => (int) $validatedData['reference_type'],
                'ACRCOICD01ID' => 'REQ',
                'ACMROICAN' => ' ',
                'CGUNNGID' => substr($order->CGUNNGID, 0, 15),
                'ACMROIFGPT' => '1753-01-01 00:00:00.000',
                'ACMROIFENT' => $fechaActual->format('Y-m-d H:i:s'),
                'ACMROIFSAL' => $fechaActual->format('Y-m-d H:i:s'),
                'ACMROIFECC' => '1753-01-01 00:00:00.000',
                'ACMROIVOLU' => $producto->INPRODVOL * $cantidadRecibida,
                'ACMROIPESOU' => $producto->INPRODPESO * $cantidadRecibida,
                'ACMROIVOLT' => $producto->INPRODVOL * $cantidadRecibida,
                'ACMROIPESOT' => $producto->INPRODPESO * $cantidadRecibida,
            ]);
    
            Log::info("Nueva entrada en acmroi insertada correctamente.");
        } catch (\Exception $e) {
            Log::error("Error al insertar en acmroi: " . $e->getMessage());
            throw $e;
        }
    }

    public function getNewCNTDOCNSIG()
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

