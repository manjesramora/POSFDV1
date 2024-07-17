<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta y estilos -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>ADMIN DASH</title>
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
                    <h1 class="mt-5" style="text-align: center;">FLETES</h1>

                    <br><br>

                    <!-- Filtro de fechas -->
                    <div class="container">
                        <div class="row align-items-center justify-content-center mb-4 h-100">
                            <div class="col-md-10">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <form method="GET" action="{{ route('freights') }}" class="d-flex align-items-end" id="filterForm">
                                        <div class="me-2">
                                            <label for="start_date" class="form-label">Desde</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}" required>
                                                <button class="btn btn-danger" type="button" onclick="document.getElementById('start_date').value = '';">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="me-2">
                                            <label for="end_date" class="form-label">Hasta</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}" required>
                                                <button class="btn btn-danger" type="button" onclick="document.getElementById('end_date').value = '';">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="me-2">
                                            <button type="submit" class="btn btn-primary">Filtrar</button>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">Mostrar todas</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="container" style="height: 60px;">
<!-- Botón para imprimir reporte -->
<div class="d-flex justify-content-center">
    <a href="{{ route('freights.pdf', request()->all()) }}" class="btn btn-secondary">
        <i class="fas fa-print mr-2"></i>Imprimir Reporte
    </a>
</div>

                    </div>
                    <!-- Begin Page Content -->
                    <div class="container-fluid">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                @php
                                                $columns = [
                                                'document_type' => 'DOC',
                                                'document_number' => 'NO.OL',
                                                'document_type1' => 'DOC',
                                                'document_number1' => 'NO.RCN',
                                                'cost' => 'COSTO',
                                                'supplier_number' => 'NO.PROV',
                                                'carrier_number' => 'NO.TRANS',
                                                'reception_date' => 'FEC.RECEP',
                                                'reference' => 'REF',
                                                'reference_type' => 'TIPO REF',
                                                'carrier_name' => 'PROVE',
                                                'supplier_name' => 'TRANS',
                                                'store' => 'ALMACEN'
                                                ];
                                                @endphp
                                                @foreach ($columns as $field => $label)
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('freights', ['sort_by' => $field, 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        {{ $label }}
                                                        @if(request('sort_by') == $field)
                                                        @if(request('sort_order') == 'asc')
                                                        <i class="fas fa-sort-up"></i>
                                                        @else
                                                        <i class="fas fa-sort-down"></i>
                                                        @endif
                                                        @else
                                                        <i class="fas fa-sort-up"></i><i class="fas fa-sort-down"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($freights as $freight)
                                            <tr>
                                                <td class="col-1 text-center align-middle">{{ $freight->document_type }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->document_number }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->document_type1 }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->document_number1 }}</td>
                                                <td class="col-1 text-center align-middle">${{ $freight->cost }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->supplier_number }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->carrier_number }}</td>
                                                <td class="col-1 text-center align-middle">{{ \Carbon\Carbon::parse($freight->reception_date)->format('d/m/Y') }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->reference }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->reference_type }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->carrier_name }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->supplier_name }}</td>
                                                <td class="col-1 text-center align-middle">{{ $freight->store }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{ $freights->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <!-- Scripts -->
        <script src="assets/vendor/jquery/jquery.min.js"></script>
        <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
        <script src="assets/vendor/chart.js/Chart.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Script para resetear los filtros -->
        <script>
            function resetFilters() {
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
                document.getElementById('filterForm').submit();
            }
        </script>
    </div>
</body>

</html>