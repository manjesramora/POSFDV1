<!-- resources/views/insdos.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos de INSDOS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Datos de INSDOS</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Producto</th>
                    <th>Exhibición</th>
                    <th>Centro de Costos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($insdosData as $data)
                    <tr>
                        <td>{{ $data->INPRODID }}</td>
                        <td>{{ $data->INSDOSQDS }}</td>
                        <td>{{ $data->INALMNID }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="d-flex justify-content-center">
            {{ $insdosData->links() }}
        </div>
    </div>
</body>
</html>
