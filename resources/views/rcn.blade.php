<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta y estilos -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Rcn</title>
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
                    <h1 class="mt-5" style="text-align: center;">RCN</h1>

                    <br><br>

                    <!-- Filtro de búsqueda y fechas combinado -->

                    <div class="row align-items-center justify-content-center mb-4 h-100" style="margin-top: -30px;">
                        <div class="d-flex justify-content-center align-items-center h-100">
                            <!-- Formulario combinado para búsqueda y filtro de fechas -->
                            <form method="GET" action="{{ route('rcn') }}" class="d-flex align-items-end" id="combinedForm" style="margin-top: 30px;" onsubmit="validateDates()">

                                <!-- Filtro de búsqueda por número de OL -->
                                <div class="input-group me-2" style="width: 300px;">
                                    <label for="start_date" class="form-label">NUM. OL</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control uper" placeholder="Buscar..." id="searchUser" name="search" value="{{ request('search') }}" onkeypress="if(event.keyCode === 13) { this.form.submit(); }">
                                    </div>
                                </div>

                                <!-- Filtro de fecha de inicio -->
                                <div class="me-2">
                                    <label for="start_date" class="form-label">DESDE:</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}" style="width: 150px;">
                                    </div>
                                </div>

                                <!-- Filtro de fecha de fin -->
                                <div class="me-2">
                                    <label for="end_date" class="form-label">HASTA:</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}" style="width: 150px;">
                                    </div>
                                </div>

                                <div class="me-2 col-md-5 position-relative">
                                    <label for="CNCDIRNOM" class="form-label">PROVEEDOR:</label>
                                    <div class="input-group">
                                        <input type="text" name="CNCDIRNOM" id="CNCDIRNOM" class="form-control uper" value="{{ request('CNCDIRNOM') }}" autocomplete="off" oninput="searchProvider()">
                                    </div>
                                    <!-- Dropdown para Proveedor -->
                                    <div id="nameDropdown" class="dropdown-menu" style="max-height: 200px; overflow-y: auto;"></div>
                                </div>


                                <!-- Botón único para buscar y filtrar -->
                                <div class="me-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>

                                <!-- Botón para mostrar todas -->
                                <div>
                                    <button type="button" class="btn btn-danger" onclick="limpiarCampos()">
                                        <i class="fas fa-eraser"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Contenido de la tabla -->
                    <div class="container-fluid">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    @if (!$filtersApplied)
                                    <!-- Mostrar un mensaje si no se han aplicado filtros -->
                                    <div class="alert alert-info text-center">
                                        Por favor, aplica filtros para ver los registros.
                                    </div>
                                    @elseif ($rcns->isEmpty())
                                    <!-- Mostrar un mensaje si no hay resultados después de aplicar los filtros -->
                                    <div class="alert alert-warning text-center">
                                        No se encontraron resultados que coincidan con los filtros aplicados.
                                    </div>
                                    @else
                                    <!-- Mostrar la tabla solo si se aplicaron filtros y hay resultados -->
                                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="col-1 text-center sortable">
                                                    <a href="{{ route('rcn', ['sort_by' => 'CNTDOCID', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        DOCUMENTO
                                                        @if(request('sort_by') == 'CNTDOCID')
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
                                                    <a href="{{ route('rcn', ['sort_by' => 'ACMROIDOC', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NUM. OL
                                                        @if(request('sort_by') == 'ACMROIDOC')
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
                                                    <a href="{{ route('rcn', ['sort_by' => 'ACMROIFREC', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        RECEPCION
                                                        @if(request('sort_by') == 'ACMROIFREC')
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
                                                    <a href="{{ route('rcn', ['sort_by' => 'CNCDIRNOM', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        PROVEEDOR
                                                        @if(request('sort_by') == 'CNCDIRNOM')
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
                                                <th class="col-1 text-center">RCNs</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rcns as $rcn)
                                            <tr>
                                                <td class="col-1 text-center align-middle">{{ $rcn->CNTDOCID }}</td>
                                                <td class="col-1 text-center align-middle">{{ $rcn->ACMROIDOC }}</td>
                                                <td class="col-1 text-center align-middle">{{ \Carbon\Carbon::parse($rcn->ACMROIFREC)->format('d/m/Y') }}</td>
                                                <td class="col-1 text-center align-middle">{{ $rcn->CNCDIRNOM }}</td>
                                                <td class="col-1 text-center align-middle">
                                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#rcnModal{{ $rcn->ACMROIDOC }}">
                                                        <i class="fa-solid fa-file-invoice"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @endif
                                </div>
                                <!-- Mostrar paginación solo si hay resultados -->
                                @if ($filtersApplied && !$rcns->isEmpty())
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $rcns->appends(request()->all())->links() }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach($rcns as $rcn)
    <!-- Modal para mostrar RCNs asociadas -->
    <div class="modal fade" id="rcnModal{{ $rcn->ACMROIDOC }}" tabindex="-1" aria-labelledby="rcnModalLabel{{ $rcn->ACMROIDOC }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rcnModalLabel{{ $rcn->ACMROIDOC }}">RCNs para OL: {{ $rcn->ACMROIDOC }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Mostrar las RCNs asociadas a la OL seleccionada que cumplen con los requisitos -->
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th class="col-2 text-center">RCN</th>
                                <th class="col-2 text-center">Fecha Recepción</th>
                                <th class="col-2 text-center">Número de Partidas</th>
                                <th class="col-2 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($allDetailedRcns->has($rcn->ACMROIDOC))
                                @foreach($allDetailedRcns[$rcn->ACMROIDOC]->unique('ACMROINDOC') as $associatedRcn)
                                    <!-- Mostrar solo las RCNs que cumplen con la condición de 10 espacios y fecha -->
                                    <tr>
                                        <td class="col-2 text-center align-middle">{{ $associatedRcn->ACMROINDOC }}</td>
                                        <td class="col-2 text-center align-middle">{{ \Carbon\Carbon::parse($associatedRcn->ACMROIFREC)->format('d/m/Y') }}</td>
                                        <td class="col-2 text-center align-middle">{{ $associatedRcn->numero_de_partidas }}</td>
                                        <td class="col-2 text-center align-middle">
                                            <!-- Botón para abrir el PDF en una nueva pestaña -->
                                            <a href="{{ route('rcn.generatePdf', $associatedRcn->ACMROINDOC) }}" target="_blank" class="btn btn-secondary">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                            <tr>
                                <td colspan="4" class="text-center">No hay RCNs asociados disponibles.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endforeach


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
        <script src="{{ asset('js/rcn.js') }}"></script>

        <!-- JavaScript para validar fechas -->
        <script>
            function validateDates() {
                var startDate = document.getElementById('start_date').value;
                var endDate = document.getElementById('end_date').value;

                if (startDate && !endDate) {
                    document.getElementById('end_date').setCustomValidity('Por favor, seleccione una fecha de fin.');
                } else if (!startDate && endDate) {
                    document.getElementById('start_date').setCustomValidity('Por favor, seleccione una fecha de inicio.');
                } else {
                    document.getElementById('end_date').setCustomValidity('');
                    document.getElementById('start_date').setCustomValidity('');
                }

                if (startDate && endDate) {
                    var start = new Date(startDate);
                    var end = new Date(endDate);

                    if (start > end) {
                        document.getElementById('end_date').setCustomValidity('La fecha de fin debe ser posterior a la fecha de inicio.');
                    } else {
                        document.getElementById('end_date').setCustomValidity('');
                    }
                }
            }

            function resetFilters() {
                document.getElementById('searchUser').value = '';
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
                document.getElementById('combinedForm').submit();
            }
        </script>
    </div>
</body>

</html>