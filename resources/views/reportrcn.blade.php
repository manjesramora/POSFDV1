<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Recepción</title>
    <style>
        @page {
            size: landscape; /* Establecer el tamaño de la página en horizontal */
        }
        /* Estilos adicionales para el contenido */
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 100%;
            margin: 20px auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center;">Reporte de Recepción</h1>
        <!-- Datos del reporte -->
        <div style="text-align: center; margin-bottom: 20px;">
            <p>Tipo de Documento: {{ $orders->first()->ACMROITDOC }}</p>
            <p>Número de Documento: {{ $orders->first()->ACMROINDOC }}</p>
            <p>Almacén: {{ $orders->first()->INALMNID }}</p>
            <p>Nombre del Proveedor: {{ $orders->first()->provider->CNCDIRNOM ?? 'N/A' }}</p>
            <p>Referencia: {{ request('ACMROIREF') }}</p>
            <p>Fecha: {{ \Illuminate\Support\Carbon::parse($orders->first()->ACMROIFDOC)->format('Y-m-d') }}</p>
            <!-- Valor del flete -->
            <p>Flete: 0</p>
        </div>
        <!-- Tabla de órdenes -->
        <table>
            <thead>
                <tr>
                    <th>NOMBRE PRODUCTO</th>
                    <th>EMPAQUE</th>
                    <th>LINEA</th>
                    <th>IDENTIFICADOR PRODUCTO</th>
                    <th>CANTIDAD REAL</th>
                    <th>CANTIDAD A RECIBIR</th>
                    <th>PRECIO UNITARIO</th>
                    <th>TOTAL SIN FLETES</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                @if ($order->ACMROICXP !== 'S')
                <tr>
                    <td>{{ $order->ACMROIDSC }}</td>
                    <td>{{ $order->ACMROIUMT }}</td>
                    <td>{{ $order->ACMROILIN }}</td>
                    <td>{{ $order->INPRODID }}</td>
                    <td>{{ $order->ACMROIQT }}</td>
                    <td>{{ $order->ACMROIQTTR }}</td>
                    <td>{{ $order->ACMROINP }}</td>
                    <td>{{ $order->ACMROING }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="text-right">Subtotal</td>
                    <td id="subtotal"></td>
                </tr>
                <tr>
                    <td colspan="7" class="text-right">Flete</td>
                    <td id="flete-display"></td>
                </tr>
                <tr>
                    <td colspan="7" class="text-right">Total</td>
                    <td id="total"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
