<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta y estilos -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>ADMIN DASH - RCN</title>
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
                    <div class="container">
                        <div class="row align-items-center justify-content-center mb-4 h-100">
                            <div class="col-md-10">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <!-- Formulario combinado para búsqueda y filtro de fechas -->
                                    <form method="GET" action="{{ route('rcn') }}" class="d-flex align-items-end" id="combinedForm" style="margin-top: 30px;">
                                        <!-- Campo de búsqueda por número de OL -->
                                        <div class="input-group me-2" style="width: 215px;">
                                            <input type="text" class="form-control uper" placeholder="Buscar número de OL" id="searchUser" name="search" value="{{ request('search') }}" onkeypress="if(event.keyCode === 13) { this.form.submit(); }">
                                        </div>
                                        <!-- Campo de fecha de inicio -->
                                        <div class="me-2">
                                            <label for="start_date" class="form-label">Desde</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}" onchange="validateDates()">
                                            </div>
                                        </div>
                                        <!-- Campo de fecha de fin -->
                                        <div class="me-2">
                                            <label for="end_date" class="form-label">Hasta</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}" onchange="validateDates()">
                                            </div>
                                        </div>
                                        <!-- Botón único para buscar y filtrar -->
                                        <div class="me-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                        </div>

                                        <!-- Botón para mostrar todas -->
                                        <div>
                                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">Mostrar todas</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido de la tabla -->
                    <div class="container-fluid">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                @php
                                                $columns = [
                                                    'CNTDOCID' => 'Tipo Documento',
                                                    'ACMROIDOC' => 'Número de OL',
                                                    'ACMROIFREC' => 'Fecha Recepción',
                                                ];
                                                @endphp
                                                @foreach ($columns as $field => $label)
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('rcn', ['sort_by' => $field, 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
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
                                                <!-- Nueva columna para mostrar el número de RCNs -->
                                                <th class="col-1 text-center">Número de RCNs</th>
                                                <th class="col-1 text-center">RCNs</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rcns as $rcn)
                                            <tr>
                                                <td class="col-1 text-center align-middle">{{ $rcn->CNTDOCID }}</td>
                                                <td class="col-1 text-center align-middle">{{ $rcn->ACMROIDOC }}</td>
                                                <td class="col-1 text-center align-middle">{{ \Carbon\Carbon::parse($rcn->ACMROIFREC)->format('d/m/Y') }}</td>
                                                <td class="col-1 text-center align-middle">{{ $rcn->numero_de_rcns }}</td>
                                                <td class="col-1 text-center align-middle">
                                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#rcnModal{{ $rcn->ACMROIDOC }}">
                                                        <i class="fa-solid fa-file-invoice"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $rcns->appends(request()->all())->links() }}
                                </div>
                            </div>
                        </div>
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
                            @foreach($allDetailedRcns[$rcn->ACMROIDOC] as $associatedRcn)
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

</body>

</html>
