<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Fletes</title>
    <style>
        body {
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 4px; /* Reduce el padding para ahorrar espacio */
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .small-text {
            font-size: 8px; /* Tamaño de fuente más pequeño para caber más contenido */
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">Reporte de Fletes</h1>
    <table>
        <thead>
            <tr>
                <th >NO.OL</th>
                <th >NO.RCN</th>
                <th >COSTO</th>
                <th >NO.PROV</th>
                <th >NO.TRANS</th>
                <th >FEC.REC</th>
                <th >PROVE</th>
                <th >TRANS</th>
                <th >ALMACEN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($freights as $freight)
            <tr>
                <td >{{ $freight->document_number }}</td>
                <td >{{ $freight->document_number1 }}</td>
                <td >${{ $freight->cost }}</td>
                <td >{{ $freight->supplier_number }}</td>
                <td >{{ $freight->carrier_number }}</td>
                <td >{{ \Carbon\Carbon::parse($freight->reception_date)->format('d/m/Y') }}</td>
                <td >{{ $freight->carrier_name }}</td>
                <td >{{ $freight->supplier_name }}</td>
                <td >{{ $freight->store }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
