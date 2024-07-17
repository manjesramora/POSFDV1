<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Empleados</title>
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
                    <h1 class="mt-5" style="text-align: center;">EMPLEADOS</h1>
                    <!-- Filtros y Botón para agregar empleado -->
                    <div class="container">
                        <form id="filtersForm" method="GET" action="{{ route('employees') }}">
                            <div class="row align-items-center justify-content-center mb-4">
                                <div class="col-md-2 mb-3">
                                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addEmployeeModal" style="margin-top: 32px;">
                                        <i class="fas fa-plus-circle mr-2"></i>Agregar Empleado
                                    </button>
                                </div>
                                <!-- Filtro buscar nombre -->
                                <div class="col-md-3 mb-3">
                                    <div class="input-group" style="margin-top: 32px;">
                                        <input type="text" class="form-control uper" placeholder="Buscar empleado" id="searchEmployee" name="search" value="{{ request('search') }}">
                                        <button class="btn btn-danger" type="button" onclick="limpiarCampos()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Filtro activos/inactivos -->
                                <div class="col-md-2 mb-3">
                                    <label for="statusFilter" class="form-label">Estado</label>
                                    <select id="statusFilter" class="form-select" name="status" onchange="document.getElementById('filtersForm').submit()">
                                        <option value="">Todos</option>
                                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Activos</option>
                                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactivos</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- /.container-fluid -->

                    <!-- Begin Page Content -->
                    <div class="container-fluid">
                        <!-- Employee Table -->
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    <table class="table table-bordered text-center table-striped" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'first_name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NOMBRE(S)
                                                        @if(request('sort_by') == 'first_name')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'last_name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        A.PATERNO
                                                        @if(request('sort_by') == 'last_name')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'middle_name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        A.MATERNO
                                                        @if(request('sort_by') == 'middle_name')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'curp', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        CURP
                                                        @if(request('sort_by') == 'curp')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'rfc', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        RFC
                                                        @if(request('sort_by') == 'rfc')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'colony', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        COLONIA
                                                        @if(request('sort_by') == 'colony')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'street', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        CALLE
                                                        @if(request('sort_by') == 'street')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'external_number', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NUM EXT
                                                        @if(request('sort_by') == 'external_number')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'internal_number', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NUM INT
                                                        @if(request('sort_by') == 'internal_number')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'postal_code', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        CP
                                                        @if(request('sort_by') == 'postal_code')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'phone', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        TELEFONO
                                                        @if(request('sort_by') == 'phone')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'phone2', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        TELEFONO 2
                                                        @if(request('sort_by') == 'phone2')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'birth', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        FECHA NACIMIENTO
                                                        @if(request('sort_by') == 'birth')
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
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('employees', ['sort_by' => 'status', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        ESTADO
                                                        @if(request('sort_by') == 'status')
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
                                                <th class="col-1 text-center align-middle sticky-col">ACCIONES</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($employees as $employee)
                                            <tr>
                                                <td class="text-center align-middle">{{ $employee->first_name }}</td>
                                                <td class="text-center align-middle">{{ $employee->last_name }}</td>
                                                <td class="text-center align-middle">{{ $employee->middle_name }}</td>
                                                <td class="text-center align-middle">{{ $employee->curp }}</td>
                                                <td class="text-center align-middle">{{ $employee->rfc }}</td>
                                                <td class="text-center align-middle">{{ $employee->colony }}</td>
                                                <td class="text-center align-middle">{{ $employee->street }}</td>
                                                <td class="text-center align-middle">{{ $employee->external_number }}</td>
                                                <td class="text-center align-middle">{{ $employee->internal_number }}</td>
                                                <td class="text-center align-middle">{{ $employee->postal_code }}</td>
                                                <td class="text-center align-middle">{{ $employee->phone }}</td>
                                                <td class="text-center align-middle">{{ $employee->phone2 }}</td>
                                                <td class="text-center align-middle">{{ $employee->birth }}</td>
                                                <td>
                                                    @if ($employee->status == 1)
                                                    <span class="badge bg-primary">ACTIVO</span>
                                                    @else
                                                    <span class="badge bg-danger">INACTIVO</span>
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle sticky-col">
                                                    <!-- Acciones -->
                                                    <div class="d-inline-block">
                                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editEmployeeModal{{ $employee->id }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div>

                                    </div>
                                    <div class="d-flex justify-content-center mt-3">
                                        {{ $employees->appends(request()->all())->links() }}
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.container-fluid -->
                </div>
                <!-- End of Main Content -->
            </div>
        </div>
    </div>

    @foreach($employees as $employee)
    <!-- Modal de Edición de Empleado -->
    <div class="modal fade text-left" id="editEmployeeModal{{ $employee->id }}" tabindex="-1" aria-labelledby="editEmployeeModalLabel{{ $employee->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel{{ $employee->id }}">Editar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editEmployeeForm{{ $employee->id }}" method="POST" action="{{ route('employees.update', $employee->id) }}">
                        @csrf
                        @method('PUT')
                        <!-- Campos del formulario -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_first_name{{ $employee->id }}" class="form-label">Nombre(s)</label>
                                    <input type="text" class="form-control uper" id="edit_first_name{{ $employee->id }}" name="first_name" value="{{ $employee->first_name }}" required maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_last_name{{ $employee->id }}" class="form-label">Apellido paterno</label>
                                    <input type="text" class="form-control uper" id="edit_last_name{{ $employee->id }}" name="last_name" value="{{ $employee->last_name }}" required maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_middle_name{{ $employee->id }}" class="form-label">Apellido materno</label>
                                    <input type="text" class="form-control uper" id="edit_middle_name{{ $employee->id }}" name="middle_name" value="{{ $employee->middle_name }}" maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_curp{{ $employee->id }}" class="form-label">CURP</label>
                                    <input type="text" class="form-control uper" id="edit_curp{{ $employee->id }}" name="curp" value="{{ $employee->curp }}" required pattern="^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]{2}$" maxlength="18" title="El CURP debe tener el formato AAAA000000HAAAAA00 y consistir de 18 caracteres.">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_rfc{{ $employee->id }}" class="form-label">RFC</label>
                                    <input type="text" class="form-control uper" id="edit_rfc{{ $employee->id }}" name="rfc" value="{{ $employee->rfc }}" required pattern="^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$" maxlength="13" title="El RFC debe tener el formato AAAA000000AAA y consistir de 13 caracteres.">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_colony{{ $employee->id }}" class="form-label">Colonia</label>
                                    <input type="text" class="form-control uper" id="edit_colony{{ $employee->id }}" name="colony" value="{{ $employee->colony }}" maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_street{{ $employee->id }}" class="form-label">Calle</label>
                                    <input type="text" class="form-control uper" id="edit_street{{ $employee->id }}" name="street" value="{{ $employee->street }}" maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_external_number{{ $employee->id }}" class="form-label">Número externo</label>
                                    <input type="text" class="form-control uper" id="edit_external_number{{ $employee->id }}" name="external_number" value="{{ $employee->external_number }}" maxlength="10">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_internal_number{{ $employee->id }}" class="form-label">Número interno</label>
                                    <input type="text" class="form-control uper" id="edit_internal_number{{ $employee->id }}" name="internal_number" value="{{ $employee->internal_number }}" maxlength="10">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_postal_code{{ $employee->id }}" class="form-label">Código postal</label>
                                    <input type="text" class="form-control" id="edit_postal_code{{ $employee->id }}" name="postal_code" value="{{ $employee->postal_code }}" pattern="\d{5}" maxlength="5" title="El código postal debe consistir de 5 dígitos.">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_phone{{ $employee->id }}" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="edit_phone{{ $employee->id }}" name="phone" value="{{ $employee->phone }}" pattern="\d{10}" maxlength="10" title="El teléfono debe consistir de 10 dígitos.">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_phone2{{ $employee->id }}" class="form-label">Teléfono 2</label>
                                    <input type="text" class="form-control" id="edit_phone2{{ $employee->id }}" name="phone2" value="{{ $employee->phone2 }}" pattern="\d{10}" maxlength="10" title="El teléfono 2 debe consistir de 10 dígitos.">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_birth{{ $employee->id }}" class="form-label">Fecha de nacimiento</label>
                                    <input type="date" class="form-control" id="edit_birth{{ $employee->id }}" name="birth" value="{{ date('Y-m-d', strtotime($employee->birth)) }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="edit_status{{ $employee->id }}" class="form-label">Estado</label>
                                <select class="form-control" id="edit_status{{ $employee->id }}" name="status">
                                    <option value="1" {{ $employee->status == 1 ? 'selected' : '' }}>ACTIVO</option>
                                    <option value="0" {{ $employee->status == 0 ? 'selected' : '' }}>INACTIVO</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Modal para agregar empleado -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Agregar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addEmployeeForm" method="POST" action="{{ route('employees.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">Nombre</label>
                                    <input type="text" class="form-control uper" id="first_name" name="first_name" required maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Apellido paterno</label>
                                    <input type="text" class="form-control uper" id="last_name" name="last_name" required maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="middle_name" class="form-label">Apellido materno</label>
                                    <input type="text" class="form-control uper" id="middle_name" name="middle_name" required maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="curp" class="form-label">CURP</label>
                                    <input type="text" class="form-control uper" id="curp" name="curp" required pattern="^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]{2}$" maxlength="18" title="El CURP debe tener el formato AAAA000000HAAAAA00">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="rfc" class="form-label">RFC</label>
                                    <input type="text" class="form-control uper" id="rfc" name="rfc" required pattern="^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$" maxlength="13" title="El RFC debe tener el formato AAAA000000AAA">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="colony" class="form-label">Colonia</label>
                                    <input type="text" class="form-control uper" id="colony" name="colony" required maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="street" class="form-label">Calle</label>
                                    <input type="text" class="form-control uper" id="street" name="street" required maxlength="50">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="external_number" class="form-label">Numero externo</label>
                                    <input type="text" class="form-control uper" id="external_number" name="external_number" required maxlength="10">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="internal_number" class="form-label">Numero interno</label>
                                    <input type="text" class="form-control uper" id="internal_number" name="internal_number" maxlength="10">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">Codigo postal</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required pattern="\d{5}" maxlength="5" title="El código postal debe consistir de 5 dígitos.">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefono</label>
                                    <input type="text" class="form-control" id="phone" name="phone" required pattern="\d{10}" maxlength="10" title="El teléfono debe consistir de 10 dígitos.">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="phone2" class="form-label">Telefono 2</label>
                                    <input type="text" class="form-control" id="phone2" name="phone2" pattern="\d{10}" maxlength="10" title="El teléfono 2 debe consistir de 10 dígitos.">
                                    <span class="error mensaje" aria-live="polite"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="birth" class="form-label">Fecha de nacimiento</label>
                                    <input type="date" class="form-control" id="birth" name="birth" required>
                                </div>
                            </div>
                            <!-- Campo de Estado -->
                            <input type="hidden" id="status" name="status" value="1">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap core JavaScript -->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/vendor/chart.js/Chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/employees.js') }}"></script>
</body>

</html>