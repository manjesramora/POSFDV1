<!DOCTYPE html>
<html>

<head>
    <title>Reporte de RCN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 4px;
            text-align: center;
            font-size: 12px;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .header img {
            float: left;
            width: 100px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 0;
            font-size: 12px;
        }

        .additional-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .info-column {
            width: 50%;
        }

        .subcolumn {
            text-align: left;
            padding-right: 10px;
        }

        .totals {
            margin-top: 10px;
            font-size: 12px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
        }

        .pagenum:before {
            content: counter(page);
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            font-size: 12px;
        }

        .total-row div {
            padding: 4px;
        }

        .no-border {
            border: none !important;
            /* No border for specific cells */
        }

        .large-font {
            font-size: 12px;
        }

        .merge-right {
            border-right: none;
        }

        .merge-left {
            border-left: none;
        }

        .no-horizontal-border {
            border-top: none !important;
            /* Quita la línea superior */
            border-bottom: none !important;
            /* Quita la línea inferior */
        }

        .merge-top {
            border-top: none !important;
            /* Quita la línea inferior */
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ public_path('assets/img/LogoFD.jpeg') }}" alt="Logo">
        <h1 style="margin-right: 100px;">REPORTE DE RCN</h1>
        <p style="margin-right: 100px;">FERRETERIA DURANGO</p>
        <br><br><br>
        <p style="margin-right: 100px;">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') ?? '00/00/0000' }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') ?? '00/00/0000' }}</p>
        <br><br>

        <!-- Datos adicionales organizados en una fila con dos subcolumnas -->
        <div class="additional-info" style="display: flex; justify-content: space-between;">
            <!-- Primera subcolumna a la izquierda -->
            <a class="info-column subcolumn" style="text-align: left;">
                <p>
                    <span style="margin-right: 150px;">Recepción: RCN {{ $numeroRcn }}</span>
                    <span>Tipo ref.: {{ $tipoRef }}</span>
                </p>

                <p>
                    <span style="margin-right: 225px;">Doc. Prov.:</span>
                    <span>No. ref.: {{ $numeroRef }}</span>
                </p>

                <p>
                    <span style="margin-right: 200px;">O.C.: OL {{ $numeroOL }}</span>
                    <span>Proveedor: {{ $nombreProveedor }}</span>
                </p>

                <p>Almacén: {{ $almacenId }}</p>
            </a>
        </div>

        @foreach ($groupedRcns as $groupKey => $rcns)
       
        <table>
            <thead>
                <tr>
                    <th>LIN</th>
                    <th>SKU</th>
                    <th colspan="2">DESCRIPCIÓN DEL ARTICULO / CODIGO DE BARRAS</th>
                    <th>U.M</th>
                    <th>PESO (KG)</th>
                    <th>VOL (MT3)</th>
                    <th>CANTIDAD</th>
                    <th>PRECIO UNI.</th>
                    <th>IMPORTE</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalPeso = 0;
                $totalVolumen = 0;
                $subtotal = 0;
                $iva = 0;
                $total = 0;
                @endphp

                @foreach($rcns as $rcn)
                <tr>
                    <td>{{ intval($rcn->ACMROILIN) }}</td>
                    <td>{{ $rcn->INPRODI2 }}</td>
                    <td class="merge-right">{{ $rcn->ACMROIDSC }}</td>
                    <td class="merge-left">{{ $rcn->INPRODI3 }}</td>
                    <td>{{ $rcn->ACMROIUMT }}</td>
                    <td>{{ number_format($rcn->ACMROIPESOU, 2) }}</td>
                    <td>{{ number_format($rcn->ACMROIVOLU, 2) }}</td>
                    <td>{{ number_format($rcn->ACMROIQT, 2) }}</td>
                    <td>{{ number_format($rcn->ACMROINP, 4) }}</td>
                    <td>{{ number_format($rcn->ACMROING, 4) }}</td>
                </tr>
                @php
                $totalPeso += $rcn->ACMROIPESOU;
                $totalVolumen += $rcn->ACMROIVOLU;
                $subtotal += $rcn->ACMROING;
                @endphp
                @endforeach

                @php
                $iva = $subtotal * 0.16; // Suponiendo un 16% de IVA
                $total = $subtotal + $iva;
                @endphp

                <!-- Fila para mostrar los totales y subtotales -->
                <tr class="no-top-border">
                    <td colspan="5" class="text-end fw-bold no-border"></td>
                    <td class="fw-bold">{{ number_format($totalPeso, 2) }}</td>
                    <td class="fw-bold">{{ number_format($totalVolumen, 2) }}</td>
                    <td colspan="2" class="text-end fw-bold merge-right no-horizontal-border">Subtotal:</td>
                    <td class="fw-bold merge-left no-horizontal-border">{{ number_format($subtotal, 4) }}</td>
                </tr>
                <tr class="no-top-border">
                    <td colspan="7" class="no-border"></td>
                    <td colspan="2" class="text-end fw-bold merge-right no-horizontal-border">IVA (16%):</td>
                    <td class="fw-bold merge-left no-horizontal-border">{{ number_format($iva, 4) }}</td>
                </tr>
                <tr>
                    <td colspan="7" class="no-border"></td>
                    <td colspan="2" class="text-end fw-bold merge-right merge-top">Total:</td>
                    <td class="fw-bold merge-left merge-top">{{ number_format($total, 4) }}</td>
                </tr>
            </tbody>
        </table>
        @endforeach
</body>

</html>