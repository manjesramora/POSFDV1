<!DOCTYPE html>
<html>

<head>
    <title>Reporte de Fletes</title>
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
        <h1 style="margin-right: 100px;">REPORTE DE FLETE</h1>
        <p style="margin-right: 100px;">FERRETERIA DURANGO</p>
        <br><br><br>
        <p style="margin-right: 100px;">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') ?? '00/00/0000' }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') ?? '00/00/0000' }}</p>
        <br><br>
    </div>

    @foreach ($groupedFreights as $supplierName => $freights)
    <div>
        <h3 style="text-align: left;">{{ $freights->first()->supplier_number }} - {{ $supplierName }}</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>NO.OL</th>
                <th>NO.RCN</th>
                <th>NO.TRANSP</th>
                <th>TRANSP</th>
                <th>ALMACEN</th>
                <th>FECHA</th>
                <th>CTO.PROV</th>
                <th>CTO.FLETE</th>
                <th>%FLETE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($freights as $freight)
            <tr>
                <td>{{ $freight->document_number }}</td>
                <td>{{ $freight->document_number1 }}</td>
                <td>{{ $freight->carrier_number }}</td>
                <td>{{ $freight->carrier_name }}</td>
                <td>{{ $freight->store }}</td>
                <td>{{ \Carbon\Carbon::parse($freight->reception_date)->format('d/m/Y') }}</td>
                <td>${{ number_format($freight->cost, 2) }}</td>
                <td>${{ number_format($freight->freight, 2) }}</td>
                <td>{{ number_format(($freight->freight / $freight->cost) * 100, 2) }}%</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="6" class="text-end fw-bold no-border">Total:</td>
                <td class="text-center fw-bold no-border">${{ number_format($freights->sum('cost'), 2) }}</td>
                <td class="text-center fw-bold no-border">${{ number_format($freights->sum('freight'), 2) }}</td>
                <td class="no-border"></td>
            </tr>
        </tbody>
    </table>
    @endforeach

    <div class="totals">
        <table>
            <tbody>
                <tr>
                    <td class="no-border fw-bold large-font" style="text-align: left;">
                        Total General CTO.PROV: <span style="padding-left: 5px;">${{ number_format($totalCost, 2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="no-border fw-bold large-font" style="text-align: left;">
                        Total General CTO.FLETE: <span style="padding-left: 5px;">${{ number_format($totalFreight, 2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="no-border fw-bold large-font" style="text-align: left;">
                        Total General: <span style="padding-left: 5px;">${{ number_format($totalCost + $totalFreight, 2) }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>