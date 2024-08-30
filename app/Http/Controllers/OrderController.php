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

    // Remove the default date range
    // Only filter by date if the user specifies them
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    if ($startDate && $endDate) {
        $query->whereBetween('ACMVOIFDOC', [$startDate, $endDate]);
    }

    // Check for exact match on ACMVOIDOC if input is filled
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

    // Sorting logic
    $sortableColumns = ['CNTDOCID', 'ACMVOIDOC', 'CNCDIRID', 'CNCDIRNOM', 'ACMVOIFDOC', 'ACMVOIALID'];
    $sortColumn = $request->input('sortColumn', 'ACMVOIDOC');
    $sortDirection = $request->input('sortDirection', 'desc');

    if (in_array($sortColumn, $sortableColumns)) {
        $query->orderBy($sortColumn, $sortDirection);
    } else {
        $query->orderBy('ACMVOIDOC', 'desc');
    }

    // Modify the subquery to filter all items by default
    $query->whereExists(function ($subquery) {
        $subquery->select(DB::raw(1))
            ->from('ACMVOR1')
            ->whereRaw('ACMVOR1.ACMVOIDOC = ACMVOR.ACMVOIDOC')
            ->whereRaw('ACMVOR1.ACMVOIQTO > ACMVOR1.ACMVOIQTR');
    });

    $orders = $query->paginate(30);

    if ($request->ajax()) {
        return view('orders_table', compact('orders', 'sortColumn', 'sortDirection'))->render();
    }

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

    public function receiptOrder(Request $request, $ACMVOIDOC)
{
    try {
        // Validate incoming data; allow nullable or empty fields
        $validatedData = $request->validate([
            'cantidad_recibida.*' => 'nullable|numeric|regex:/^\d+(\.\d{1,4})?$/',  // max 4 decimals, can be null
            'precio_unitario.*' => 'nullable|numeric|regex:/^\d+(\.\d{1,4})?$/',  // max 4 decimals, can be null
            'document_number1' => 'required',  // Ensure document_number1 is included and required
        ], [
            'numeric' => 'El campo :attribute debe ser un número.',
            'regex' => 'El campo :attribute no puede contener más de 4 decimales.',
            'document_number1.required' => 'El número de documento es obligatorio.',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Log validation errors
        Log::error('Errores de validación:', $e->errors());
        return response()->json([
            'success' => false,
            'errors' => $e->errors()
        ], 422);
    }

    $order = Order::where('ACMVOIDOC', $ACMVOIDOC)->first();
    $provider = Providers::where('CNCDIRID', $order->CNCDIRID)->first();

    if (!$order || !$provider) {
        return response()->json(['success' => false, 'message' => 'Orden o proveedor no encontrado.']);
    }

    Log::info('Datos recibidos en receiptOrder:', $request->all());

    DB::beginTransaction();

    try {
        foreach ($validatedData['cantidad_recibida'] as $index => $cantidadRecibida) {
            if ($cantidadRecibida === null || $cantidadRecibida === '') {
                continue;  // Skip empty or null received quantities
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

            // Round values to 4 decimals and format as string to avoid scientific notation
            $cantidadRecibida = number_format((float) round($cantidadRecibida, 4), 4, '.', '');
            $precioUnitario = number_format((float) round($precioUnitario, 4), 4, '.', '');

            $cantidadSolicitada = (float) $request->input('acmvoiqtp')[$index] > 0 ? $request->input('acmvoiqtp')[$index] : $request->input('acmvoiqto')[$index];
            $cantidadSolicitada = number_format((float) round($cantidadSolicitada, 4), 4, '.', '');

            DB::table('ACMVOR1')
                ->where('ACMVOIDOC', $ACMVOIDOC)
                ->where('ACMVOILIN', $request->input('acmvoilin')[$index])
                ->update([
                    'ACMVOIQTR' => DB::raw('ACMVOIQTR + ' . $cantidadRecibida),
                    'ACMVOIQTP' => $cantidadSolicitada - $cantidadRecibida,
                ]);
        }

        // Call insertPartidas only if there are valid data to process
        $this->insertPartidas($request->all(), $validatedData['cantidad_recibida'], $validatedData['precio_unitario'], $order, $provider);

        DB::commit();

        Log::info('Recepción registrada con éxito en la base de datos.');

        return response()->json([
            'success' => true,
            'message' => 'Recepción registrada con éxito.',
            'ACMROINDOC' => $validatedData['document_number1'],  // Correctly get the value now
            'ACMROIDOC' => $order->ACMVOIDOC
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error en la recepción de la orden: " . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Ocurrió un error al registrar la recepción.']);
    }
}

public function generatePdf(Request $request)
{
    $ACMROINDOC = $request->input('ACMROINDOC');

    if (!$ACMROINDOC) {
        return redirect()->route('rcn.index')->with('error', 'Parámetro de reporte inválido.');
    }

    // Buscar los registros utilizando ACMROINDOC
    $records = DB::table('ACMROI')
        ->select('ACMROITDOC', 'ACMROINDOC', 'CNTDOCID', 'ACMROIDOC', 'ACMROIFREC', DB::raw('COUNT(*) as numero_de_partidas'))
        ->where('ACMROINDOC', $ACMROINDOC)
        ->groupBy('ACMROITDOC', 'ACMROINDOC', 'CNTDOCID', 'ACMROIDOC', 'ACMROIFREC')
        ->get();

    if ($records->isEmpty()) {
        return redirect()->route('rcn.index')->with('error', 'No se encontraron registros para el reporte.');
    }

    // Agrupar los datos por CNTDOCID o cualquier otro campo relevante
    $groupedRcns = $records->groupBy('CNTDOCID');

    $startDate = $records->min('ACMROIFREC'); // Usar la fecha más temprana del grupo
    $endDate = $records->max('ACMROIFREC'); // Usar la fecha más reciente del grupo

    // Generar el PDF utilizando la vista `report_rcn`
    $pdf = PDF::loadView('report_rcn', [
        'groupedRcns' => $groupedRcns,
        'startDate' => $startDate,
        'endDate' => $endDate
    ]);

    // Agregar numeración de páginas
    $pdf->output();
    $canvas = $pdf->getDomPDF()->getCanvas();
    $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
        $text = "Página $pageNumber de $pageCount";
        $font = $fontMetrics->get_font('Arial', 'normal');
        $size = 8;
        $width = $canvas->get_width();
        $height = $canvas->get_height();
        $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
        $canvas->text($width - $textWidth - 20, $height - 20, $text, $font, $size);
    });

    return $pdf->download('rcn_report_' . $ACMROINDOC . '.pdf');
}





public function insertPartidas($validatedData, $cantidadesRecibidas, $preciosUnitarios, $order, $provider)
{
    $fechaActual = now();
    $horaActual = now()->format('H:i:s');
    $usuario = Auth::user()->name ?? 'Sistema';

    try {
        foreach ($cantidadesRecibidas as $index => $cantidadRecibida) {
            if ($cantidadRecibida > 0) {
                if (!isset($validatedData['acmvoilin'][$index]) || 
                    !isset($validatedData['acmvoiprid'][$index]) || 
                    !isset($validatedData['acmvoiprds'][$index]) || 
                    !isset($validatedData['acmvoiumt'][$index]) || 
                    !isset($validatedData['acmvoiiva'][$index])) {
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
        try {
            $acmroilin = isset($partida['ACMVOILIN']) ? (int) $partida['ACMVOILIN'] : 0;
            if ($acmroilin === 0) {
                throw new \Exception("El valor de ACMVOILIN es nulo o cero para la partida con ID {$partida['ACMVOIPRID']}");
            }
    
            $producto = DB::table('inprod')
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
            $acmroiVolt = number_format((float) ($producto->INPRODVOL * $cantidadRecibida), 6, '.', '');
            $acmroiPesot = number_format((float) ($producto->INPRODPESO * $cantidadRecibida), 6, '.', '');
    
            $insertData = [
                'CNCIASID' => 1,
                'ACMROITDOC' => 'RCN',
                'ACMROINDOC' => isset($validatedData['document_number1']) ? (int) $validatedData['document_number1'] : 0,
                'CNTDOCID' => 'OL1',
                'ACMROIDOC' => isset($order->ACMVOIDOC) ? (int) $order->ACMVOIDOC : 0,
                'ACMROILIN' => $acmroilin,
                'ACMROIFREC' => $order->ACMROIFREC ?? null,
                'CNCDIRID' => isset($provider->CNCDIRID) ? (int) $provider->CNCDIRID : 0,
                'INALMNID' => isset($validatedData['store']) ? substr($validatedData['store'], 0, 15) : '',
                'ACMVOIAOD' => isset($order->ACMVOIAOD) ? substr($order->ACMVOIAOD, 0, 3) : '',
                'CNCMNMID' => isset($order->CNCMNMID) ? substr($order->CNCMNMID, 0, 3) : '',
                'ACMROIFDOC' => $order->ACMROIFDOC ?? null,
                'ACMROIUSRC' => $usuario,
                'ACMROIFCEP' => $order->ACMROIFCEP ?? null,
                'ACMROIFREQ' => $order->ACMROIFREQ ?? null,
                'ACMROIFTRN' => $order->ACMROIFTRN ?? null,
                'ACMROIFCNT' => $order->ACMROIFCNT ?? null,
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
                'ACMROIDOI3' => 'PC',
                'ACMROIDOC3' => $this->getNewCNTDOCNSIG(),
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
                'ACMROIFOC' => '1753-01-01 00:00:00.000',
                'ACMROICXP' => 'N',
                'ACMROIFDC' => $order->ACMROIFCEP ?? null,
                'ACMROIFVN' => '1753-01-01 00:00:00.000',
                'ACMROING2' => $costoTotal,
                'ACMROINI2' => $costoTotal * 0.16,
                'ACMROINM2' => $costoTotal * 1.16,
                'ACMROIVOLU' => $acmroiVolu,
                'ACMROIPESOU' => $acmroiPesou,
                'ACMROIVOLT' => $acmroiVolt,
                'ACMROIPESOT' => $acmroiPesot,
            ];
    
            Log::info('Datos que se insertarán en acmroi:', $insertData);
    
            $inserted = DB::table('acmroi')->insert($insertData);
    
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
        try {
            $existingRecord = DB::table('insdos')
                ->where('INALMNID', $storeId)
                ->where('INPRODID', $productId)
                ->first();

            if ($existingRecord) {
                $updated = DB::table('insdos')
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
                $inserted = DB::table('insdos')->insert([
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
