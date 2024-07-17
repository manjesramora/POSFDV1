<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVPREC Data</title>
    <!-- Bootstrap core CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">AVPREC Data</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID PRODUCTO</th>
                    <th>PRECIO BASE</th>
                    <th>FECHA INICIAL</th>
                    <th>ALMACEN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>{{ $item->AVPRECPRO }}</td>
                    <td>{{ $item->AVPRECBAS }}</td>
                    <td>{{ $item->AVPREFIPB }}</td>
                    <td>{{ $item->AVPRECALM }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
