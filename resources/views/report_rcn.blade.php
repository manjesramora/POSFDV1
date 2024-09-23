<!DOCTYPE html>
<html>

<head>
    <title>Reporte de RCN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px; /* Reducido a 10px para ajustar mejor a la hoja */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10px; /* Reducido a 10px para ajustar mejor a la hoja */
            table-layout: auto; /* Permitir que las columnas se ajusten automáticamente */
        }

        th,
        td {
            border: 1px solid black;
            padding: 2px; /* Reducir el padding para ganar más espacio */
            text-align: center;
            font-size: 10px;
            word-wrap: break-word; /* Ajustar palabras largas a la siguiente línea */
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
            font-size: 10px;
        }

        .header img {
            float: left;
            width: 80px; /* Reducir el tamaño de la imagen */
        }

        .header h1 {
            margin: 0;
            font-size: 18px; /* Reducir el tamaño de la fuente del título */
        }

        .header p {
            margin: 0;
            font-size: 10px;
        }

        .additional-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 10px;
        }

        .totals {
            margin-top: 10px;
            font-size: 10px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }

        .pagenum:before {
            content: counter(page);
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            font-size: 10px;
        }

        .total-row div {
            padding: 4px;
        }

        .no-border {
            border: none !important;
        }

        .large-font {
            font-size: 10px;
        }

        .merge-right {
            border-right: none;
        }

        .merge-left {
            border-left: none;
        }

        .no-horizontal-border {
            border-top: none !important;
            border-bottom: none !important;
        }

        .merge-top {
            border-top: none !important;
        }

        .signature-container {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-line {
            text-align: center;
            width: 200px;
            margin-top: 50px;
            border-top: 1px solid black;
        }

        .table-container {
            margin-bottom: 100px;
            page-break-inside: auto;
        }

        .signature-area {
            text-align: center;
            position: relative;
            page-break-inside: avoid;
        }

        .barcode {
            word-break: break-all;
            white-space: normal;
            max-width: 100px;
            /* Asegura que el texto se ajuste al ancho máximo */
            overflow-wrap: break-word;
            /* Asegura que se rompan las palabras largas */
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ public_path('assets/img/LogoFD.jpeg') }}" alt="Logo">
        <h1 style="margin-right: 100px; font-size: 18px;">REPORTE DE RCN</h1>
        <p style="margin-right: 100px; font-size: 12px;">FERRETERIA DURANGO</p>
        <br><br><br>
        <p style="margin-right: 100px;"><strong>Fecha de Elaboración:</strong> {{ $fechaElaboracion }}</p>
        <p style="margin-right: 11.5px;"><strong>Fecha de Impresión:</strong> {{ $fechaImpresion }}</p>
        <br><br>

        <div class="additional-info">
            <a class="info-column subcolumn" style="text-align: left;">
                <p>
                    <span style="margin-right: 145px;"><strong>Recepción:</strong> RCN {{ $numeroRcn }}</span>
                    <span><strong>Tipo ref.:</strong> {{ $tipoRef }}</span>
                </p>
                <p style="margin-top: 5px;">
                    <span><strong style="margin-right: 220px;">Doc. Prov.:</strong></span>
                    <span><strong>No. ref.:</strong> {{ $numeroRef }}</span>
                </p>
                <p style="margin-top: 5px;">
                    <span style="margin-right: 199px;"><strong>O.C.:</strong> OL {{ $numeroOL }}</span>
                    <span><strong>Proveedor:</strong> {{ $nombreProveedor }}</span>
                </p>
                <p style="margin-top: 15px;">
                    <strong>Almacén:</strong> {{ $almacenId }} CENTRO DE DISTRIBUCIÓN {{ $branchName }}
                </p>
            </a>
        </div>

        <!-- Table content -->
        <div class="table-container">
            @foreach ($groupedRcns as $groupKey => $rcns)
            <table>
                <thead>
                    <tr>
                        <th>LIN</th>
                        <th>SKU</th>
                        <th>DESCRIPCIÓN</th>
                        <th>CODIGO DE BARRAS</th>
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
                        <td class="barcode">{{ $rcn->INPRODCBR }}</td>
                        <td>{{ $rcn->ACMROIUMT }}</td>
                        <td>{{ number_format($rcn->ACMROIPESOU, 2) }}</td>
                        <td>{{ number_format($rcn->ACMROIVOLU, 2) }}</td>
                        <td>{{ number_format($rcn->ACMROIQT, 2) }}</td>
                        <td>{{ '$' . number_format($rcn->ACMROINP, 2) }}</td>
                        <td>{{ '$' . number_format($rcn->ACMROING, 2) }}</td>
                    </tr>
                    @php
                    $totalPeso += $rcn->ACMROIPESOU;
                    $totalVolumen += $rcn->ACMROIVOLU;
                    $subtotal += $rcn->ACMROING;
                    @endphp
                    @endforeach

                    @php
                    $iva = $subtotal * 0.16;
                    $total = $subtotal + $iva;
                    @endphp

                    <tr class="no-top-border">
                        <td colspan="5" class="text-end fw-bold no-border"></td>
                        <td class="fw-bold">{{ number_format($totalPeso, 2) }}</td>
                        <td class="fw-bold">{{ number_format($totalVolumen, 2) }}</td>
                        <td colspan="2" class="text-end fw-bold merge-right no-horizontal-border">Subtotal:</td>
                        <td class="fw-bold merge-left no-horizontal-border">{{ '$' . number_format($subtotal, 2) }}</td>
                    </tr>
                    <tr class="no-top-border">
                        <td colspan="7" class="no-border"></td>
                        <td colspan="2" class="text-end fw-bold merge-right no-horizontal-border">IVA (16%):</td>
                        <td class="fw-bold merge-left no-horizontal-border">{{ '$' . number_format($iva, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="no-border"></td>
                        <td colspan="2" class="text-end fw-bold merge-right merge-top">Total:</td>
                        <td class="fw-bold merge-left merge-top">{{ '$' . number_format($total, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            @endforeach
        </div>

        <!-- Signature section -->
        <div class="signature-area" style="position: absolute; bottom: 70px; width: 100%; text-align: center;">
            <div style="display: inline-block; margin-right: 50px; text-align: center;">
                ___________________________________
                <p style="margin-top: 10px;">Bo. Vo.</p>
            </div>
            <div style="display: inline-block; text-align: center;">
                ___________________________________
                <p style="margin-top: 10px;">ALMACENISTA</p>
            </div>
        </div>
</body>

</html>
