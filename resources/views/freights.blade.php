<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta y estilos -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Fletes</title>
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

                    <!-- Filtro de Proveedor Nombre -->


                    <div class="col-md-10">
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <form method="GET" action="{{ route('freights') }}" class="d-flex align-items-end" id="filterForm">
                                <!-- Filtro de fechas (ya existente) -->
                                <div class="me-2">
                                    <label for="start_date" class="form-label">DESDE:</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                                    </div>
                                </div>
                                <div class="me-2">
                                    <label for="end_date" class="form-label">HASTA:</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                                    </div>
                                </div>

                                <!-- Filtro de Proveedor Nombre -->
                                <div class="me-2 col-md-5 position-relative">
                                    <label for="CNCDIRNOM" class="form-label">PROVEEDOR:</label>
                                    <div class="input-group">
                                        <input type="text" name="CNCDIRNOM" id="CNCDIRNOM" class="form-control uper" value="" autocomplete="off">
                                    </div>
                                    <!-- Dropdown para Proveedor -->
                                    <div id="nameDropdown" class="dropdown-menu"></div>
                                </div>

                                <!-- Filtro de Transportista Nombre -->
                                <div class="me-2 col-md-5 position-relative">
                                    <label for="CNCDIRNOM_TRANSP" class="form-label">TRANSPORTISTA:</label>
                                    <div class="input-group">
                                        <input type="text" name="CNCDIRNOM_TRANSP" id="CNCDIRNOM_TRANSP" class="form-control uper" value="" autocomplete="off">
                                    </div>

                                    <!-- Dropdown para Transportista -->
                                    <div id="transporterDropdown" class="dropdown-menu"></div>
                                </div>

                                <div class="me-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>


                    <!-- Botón para imprimir reporte, deshabilitado si no se aplicaron filtros -->
                    <div class="container" style="height: 60px; margin-top: 40px;">
                        <div class="d-flex justify-content-center">
                            <div class="me-2">
                                <!-- Verifica si se aplicaron filtros -->
                                @if($filtersApplied)
                                <a href="{{ route('freights.pdf', request()->all()) }}" class="btn btn-secondary" target="_blank">
                                    <i class="fas fa-print mr-2"></i> Imprimir Reporte
                                </a>
                                @else
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-print mr-2"></i> Imprimir Reporte
                                </button>
                                @endif
                            </div>
                            <div>
                                <!-- Botón para limpiar campos -->
                                <button type="button" class="btn btn-danger" onclick="limpiarCampos()">
                                    <i class="fas fa-eraser"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Begin Page Content -->
                    <div class="container-fluid">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    @if ($freights->isEmpty() && (request()->has('start_date') || request()->has('end_date') || request()->has('CNCDIRNOM') || request()->has('CNCDIRNOM_TRANSP')))
                                    <!-- Mostrar mensaje si no se encontraron resultados después de aplicar los filtros -->
                                    <div class="alert alert-warning text-center">
                                        No se encontraron resultados que coincidan con los filtros aplicados.
                                    </div>
                                    @elseif (!$freights->isEmpty())
                                    <!-- Mostrar tabla si hay resultados -->
                                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'document_number', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NO.OL
                                                        @if(request('sort_by') == 'document_number')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'document_number1', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NO.RCN
                                                        @if(request('sort_by') == 'document_number1')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'supplier_number', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NO.PROV
                                                        @if(request('sort_by') == 'supplier_number')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'supplier_name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        PROV
                                                        @if(request('sort_by') == 'supplier_name')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'carrier_number', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NO.TRANSP
                                                        @if(request('sort_by') == 'carrier_number')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'carrier_name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        TRANSP
                                                        @if(request('sort_by') == 'carrier_name')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'reception_date', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        FECHA
                                                        @if(request('sort_by') == 'reception_date')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'cost', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        CTO.PROV
                                                        @if(request('sort_by') == 'cost')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'freight', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        CTO.FLETE
                                                        @if(request('sort_by') == 'freight')
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
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('freights', ['sort_by' => 'freight', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        CTO.FLETE
                                                        @if(request('sort_by') == 'freight')
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($freights as $freight)
                                            <tr>
                                                <td class="col-1 text-center">{{ $freight->document_number }}</td>
                                                <td class="col-1 text-center">{{ $freight->document_number1 }}</td>
                                                <td class="col-1 text-center">{{ $freight->supplier_number }}</td>
                                                <td class="col-1 text-center">{{ $freight->supplier_name }}</td>
                                                <td class="col-1 text-center">{{ $freight->carrier_number }}</td>
                                                <td class="col-1 text-center">{{ $freight->carrier_name }}</td>
                                                <td class="col-1 text-center">{{ \Carbon\Carbon::parse($freight->reception_date)->format('d/m/Y') }}</td>
                                                <td class="col-1 text-center">${{ number_format($freight->cost, 2) }}</td>
                                                <td class="col-1 text-center">${{ number_format($freight->freight, 2) }}</td>
                                                <td class="col-1 text-center">{{ number_format(($freight->freight / $freight->cost) * 100, 2) }}%</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <!-- Paginación -->
                                    <div class="d-flex justify-content-center mt-3">
                                        {{ $freights->appends(request()->all())->links() }}
                                    </div>

                                    @else
                                    <!-- Mostrar mensaje si no hay filtros aplicados -->
                                    <div class="alert alert-info text-center">
                                        Por favor, aplica filtros para ver los fletes.
                                    </div>
                                    @endif
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
                    <script src="{{ asset('js/freights.js') }}"></script>
                </div>
</body>

</html>