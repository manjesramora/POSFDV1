<!DOCTYPE html>
<html>

<head>
    <title>Reporte de RCN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 4px;
            text-align: center;
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
            font-size: 16px;
        }

        .totals {
            margin-top: 10px;
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
        }

        .total-row div {
            padding: 4px;
        }

        .no-border {
            border: none !important;
        }

        .large-font {
            font-size: 12px;
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
    </div>

    @foreach ($groupedRcns as $groupKey => $rcns)
    <div>
        <h3 style="text-align: left;">{{ $groupKey }}</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>LIN</th>
                <th>SKU</th> <!-- New Column -->
                <th>DESCRIPCIÓN DEL ARTICULO / CODIGO DE BARRAS</th>
                <th></th> <!-- New Column -->
                <th>U.M</th>
                <th>PESO (KG)</th>
                <th>VOL (MT3)</th>
                <th>CANTIDAD</th>
                <th>PRECIO UNI.</th>
                <th>IMPORTE</th>
                
                
            </tr>
        </thead>
        <tbody>
            @foreach($rcns as $rcn)
            <tr>
                <td>{{ $rcn->ACMROILIN }}</td>
                <td>{{ $rcn->INPRODI2 }}</td> <!-- New Data -->
                <td>{{ $rcn->ACMROIDSC }}</td>
                <td>{{ $rcn->INPRODI3 }}</td> <!-- New Data -->
                <td>{{ $rcn->ACMROIUMT }}</td>
                <td>{{ $rcn->ACMROIPESOU }}</td>
                <td>{{ $rcn->ACMROIVOLU }}</td>
                <td>{{ $rcn->ACMROIQT }}</td>
                <td>{{ $rcn->ACMROINP }}</td>
                <td>{{ $rcn->ACMROING }}</td>
                
                
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
</body>

</html>
