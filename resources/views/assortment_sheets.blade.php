<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Hoja de surtido</title>
    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sb-admin-2.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body id="page-top">
    <div id="wrapper">
        @include('slidebar')
        <div id="content-wrapper" class="d-flex flex-column dash" style="overflow-y: hidden;">
            <div id="content">
                @include('navbar')
                <div class="container-fluid">
                    <h1 class="mt-5" style="text-align: center;">HOJA DE SURTIDO</h1>

                    <!-- /.container-fluid -->

                    <!-- Begin Page Content -->
                    <div class="container-fluid">

                        <!-- Assortment sheets Table -->
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    <table class="table table-bordered text-center table-striped">
                                        <thead>
                                            <tr>
                                                <th>ALMACEN</th>
                                                <th>CIA.</th>
                                                <th>T.DOC</th>
                                                <th>No.DOC</th>
                                                <th>FECHA</th>
                                                <th>CLIENTE</th>
                                                <th>FACTURAR A</th>
                                                <th>ENVIAR A</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if($assortmentSheets->isEmpty())
                                            <tr>
                                                <td colspan="8" class="text-center">No hay registros disponibles</td> <!-- Cambia el número de columnas aquí -->
                                            </tr>
                                            @else
                                            @foreach($assortmentSheets as $sheet)
                                            <tr>
                                                <td>{{ $sheet->INALMNID }}</td>
                                                <td>{{ $sheet->CNCIASID }}</td>
                                                <td>{{ $sheet->CNTDOCID }}</td>
                                                <td>{{ $sheet->AVMVORDOC }}</td>
                                                <td>{{ \Carbon\Carbon::parse($sheet->AVMVORFSPC)->format('d/m/Y') }}</td>
                                                <td>
                                                    {{ $sheet->AVMVORCXC }} - {{ $clients[$sheet->AVMVORCXC] ?? 'No disponible' }}
                                                </td>
                                                <td>
                                                    {{ $sheet->AVMVORCXC }} - {{ $clients[$sheet->AVMVORCXC] ?? 'No disponible' }}
                                                </td>
                                                <td>
                                                    {{ $sheet->AVMVORCXC }} - {{ $clients[$sheet->AVMVORCXC] ?? 'No disponible' }}
                                                </td>
                                            </tr>
                                            @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Paginación -->
                                <div class="d-flex justify-content-center">
                                    {{ $assortmentSheets->links() }}
                                </div>
                            </div>
                        </div>
                        <!-- End Assortment sheets Table -->

                    </div>
                    <!-- /.container-fluid -->

                </div>
                <!-- End of Main Content -->

            </div>
            <!-- End of Page Wrapper -->


            <!-- Bootstrap core JavaScript -->
            <script src="assets/vendor/jquery/jquery.min.js"></script>
            <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
            <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
            <script src="assets/vendor/chart.js/Chart.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
            <!--<script src="{{ asset('js/assortment_sheets.js') }}"></script>
</body>

</html>