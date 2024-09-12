<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Ordenes de Compra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/sb-admin-2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body id="page-top">
    <div id="wrapper">
        @include('slidebar') <!-- Asegúrate de tener un archivo sidebar.blade.php en resources/views -->
        <div id="content-wrapper" class="d-flex flex-column dash" style="overflow-y: hidden;">
            <div id="content">
                @include('navbar') <!-- Asegúrate de tener un archivo navbar.blade.php en resources/views -->
                <div class="container-fluid">
                    <h1 class="mt-5 text-center">ORDENES DE COMPRA</h1>
                    <br>
                    <form method="GET" action="{{ route('orders') }}" class="mb-3" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <!-- NUM. OL -->
                            <div class="col-12 col-sm-6 col-md-2">
                                <label for="ACMROIDOC" class="form-label">NUM. OL:</label>
                                <input type="number" min="1" max="999999" class="form-control input-no-spinner" name="ACMVOIDOC" id="ACMVOIDOC" value="{{ request('ACMVOIDOC') }}" inputmode="numeric" autocomplete="off">
                            </div>

                            <!-- NUM. PROVEEDOR -->
                            <div class="col-12 col-sm-6 col-md-2">
                                <label for="CNCDIRID" class="form-label">NUM. PROVEDOR:</label>
                                <input type="number" class="form-control input-no-spinner" name="CNCDIRID" id="CNCDIRID" value="{{ request('CNCDIRID') }}" inputmode="numeric" autocomplete="off">
                                <div id="idDropdown" class="dropdown-menu"></div>
                            </div>

                            <!-- NOMBRE DE PROVEEDOR -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="CNCDIRNOM" class="form-label">NOMBRE DE PROVEDOR:</label>
                                <div class="input-group">
                                    <input type="text" name="CNCDIRNOM" id="CNCDIRNOM" class="form-control uper" value="{{ request('CNCDIRNOM') }}" autocomplete="off">
                                    <div id="nameDropdown" class="dropdown-menu"></div>
                                </div>
                            </div>

                            <!-- FECHA DE INICIO -->
                            <div class="col-12 col-sm-6 col-md-2">
                                <label for="start_date" class="form-label">FECHA DE INICIO:</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>

                            <!-- FECHA DE FIN -->
                            <div class="col-12 col-sm-6 col-md-2">
                                <label for="end_date" class="form-label">FECHA DE FIN:</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>

                            <!-- BOTONES -->
                            <div class="col-12 col-sm-6 col-md-1 d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button class="btn btn-danger" type="button" onclick="limpiarCampos()">
                                    <i class="fa-solid fa-eraser"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabla de órdenes -->
                <div class="table-responsive">
                    <div class="container-fluid">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    @if ($orders->isEmpty() && (request()->has('ACMVOIDOC') || request()->has('CNCDIRID') || request()->has('CNCDIRNOM') || request()->has('start_date') || request()->has('end_date')))
                                    <!-- Mostrar mensaje si no se encontraron órdenes después de aplicar los filtros -->
                                    <div class="alert alert-warning text-center">
                                        No se encontraron órdenes que coincidan con los filtros aplicados.
                                    </div>
                                    @elseif (!$orders->isEmpty())
                                    <table class="table table-bordered table-centered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="col-md-1">
                                                    <a href="{{ route('orders', ['sortColumn' => 'ACMVOIDOC', 'sortDirection' => ($sortColumn == 'ACMVOIDOC' && $sortDirection == 'asc') ? 'desc' : 'asc'] + request()->query()) }}" class="btn btn-link p-0">
                                                        #DOCUMENTO
                                                        @if($sortColumn == 'ACMVOIDOC')
                                                        <i class="fas {{ $sortDirection == 'asc' ? 'fa-sort-up' : 'fa-sort-down' }}"></i>
                                                        @else
                                                        <i class="fas fa-sort"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="col-md-1">
                                                    <a href="{{ route('orders', ['sortColumn' => 'CNCDIRID', 'sortDirection' => ($sortColumn == 'CNCDIRID' && $sortDirection == 'asc') ? 'desc' : 'asc'] + request()->query()) }}" class="btn btn-link p-0">
                                                        #PROVEEDOR
                                                        @if($sortColumn == 'CNCDIRID')
                                                        <i class="fas {{ $sortDirection == 'asc' ? 'fa-sort-up' : 'fa-sort-down' }}"></i>
                                                        @else
                                                        <i class="fas fa-sort"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="col-md-2">
                                                    <a href="{{ route('orders', ['sortColumn' => 'CNCDIRNOM', 'sortDirection' => ($sortColumn == 'CNCDIRNOM' && $sortDirection == 'asc') ? 'desc' : 'asc'] + request()->query()) }}" class="btn btn-link p-0">
                                                        NOMBRE PROVEEDOR
                                                        @if($sortColumn == 'CNCDIRNOM')
                                                        <i class="fas {{ $sortDirection == 'asc' ? 'fa-sort-up' : 'fa-sort-down' }}"></i>
                                                        @else
                                                        <i class="fas fa-sort"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="col-md-2">
                                                    <a href="{{ route('orders', ['sortColumn' => 'ACMVOIFDOC', 'sortDirection' => ($sortColumn == 'ACMVOIFDOC' && $sortDirection == 'asc') ? 'desc' : 'asc'] + request()->query()) }}" class="btn btn-link p-0">
                                                        FECHA DE ORDEN
                                                        @if($sortColumn == 'ACMVOIFDOC')
                                                        <i class="fas {{ $sortDirection == 'asc' ? 'fa-sort-up' : 'fa-sort-down' }}"></i>
                                                        @else
                                                        <i class="fas fa-sort"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="col-md-2">
                                                    <a href="{{ route('orders', ['sortColumn' => 'ACMVOIALID', 'sortDirection' => ($sortColumn == 'ACMVOIALID' && $sortDirection == 'asc') ? 'desc' : 'asc'] + request()->query()) }}" class="btn btn-link p-0">
                                                        ALMACEN
                                                        @if($sortColumn == 'ACMVOIALID')
                                                        <i class="fas {{ $sortDirection == 'asc' ? 'fa-sort-up' : 'fa-sort-down' }}"></i>
                                                        @else
                                                        <i class="fas fa-sort"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="col-md-1">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $order)
                                            <tr>
                                                <td>{{ $order->ACMVOIDOC }}</td>
                                                <td>{{ $order->CNCDIRID }}</td>
                                                <td>{{ $order->provider->CNCDIRNOM }}</td>
                                                <td>{{ \Carbon\Carbon::parse($order->ACMVOIFDOC)->format('d-m-Y') }}</td>
                                                <td>{{ $order->ACMVOIALID }}</td>
                                                <td>
                                                    <a href="{{ route('receptions.show', $order->ACMVOIDOC) }}" class="btn btn-primary">
                                                        <i class="fas fa-truck"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @else
                                    <!-- Pantalla sin cargar registros cuando no se ha aplicado ningún filtro -->
                                    <div class="alert alert-info text-center">
                                        Por favor, aplica filtros para ver las órdenes de compra.
                                    </div>
                                    @endif
                                    <div class="d-flex justify-content-center">
                                        @if ($orders instanceof \Illuminate\Pagination\LengthAwarePaginator || $orders instanceof \Illuminate\Pagination\Paginator)
                                        {{ $orders->appends(request()->except('page'))->links() }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- Coloca esto al final del body -->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/vendor/chart.js/Chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/order.js') }}"></script>
</body>

</html>