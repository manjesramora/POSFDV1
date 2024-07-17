<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Usuarios</title>
    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sb-admin-2.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body id="page-top" class="body">
    <div id="wrapper">
        @include('slidebar')
        <div id="content-wrapper" class="d-flex flex-column dash" style="overflow-y: hidden;">
            <div id="content">
                @include('navbar')
                <div class="container-fluid">
                    <h1 class="mt-5" style="text-align: center;">USUARIOS</h1>

                    <br>

                    <!-- Filtros y Botón de Añadir Usuario -->
                    <form id="filtersForm" method="GET" action="{{ route('users') }}">
                        <div class="container">
                            <div class="row align-items-center justify-content-center mb-4">
                                <div class="col-md-2 mb-3">
                                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addUserModal" style="margin-top: 32px;">
                                        <i class="fas fa-plus-circle mr-2"></i> Generar Usuario
                                    </button>
                                </div>


                                <div class="col-md-3 mb-3">
                                    <div class="input-group" style="margin-top: 32px;">
                                        <input type="text" class="form-control uper" placeholder="Buscar usuario o empleado" id="searchUser" name="search" value="{{ request('search') }}" oninput="filterEmployees()">
                                        <button class="btn btn-danger" type="button" onclick="limpiarCampos()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>


                                <div class="col-md-2 mb-3">
                                    <label for="roleFilter" class="form-label">Roles</label>
                                    <select id="roleFilter" class="form-select" name="role" onchange="document.getElementById('filtersForm').submit()">
                                        <option value="">Todos</option>
                                        @foreach($roles as $role)
                                        <option value="{{ strtolower($role->name) }}" {{ request('role') == strtolower($role->name) ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-1-5 mb-3">
                                    <label for="costCenterFilter" class="form-label">Centros de Costo</label>
                                    <select id="costCenterFilter" class="form-select" name="cost_center" onchange="document.getElementById('filtersForm').submit()">
                                        <option value="">Todos</option>
                                        @foreach($centers as $center)
                                        <option value="{{ strtolower($center->cost_center_id) }}" {{ request('cost_center') == strtolower($center->cost_center_id) ? 'selected' : '' }}>{{ $center->cost_center_id }}</option>
                                        @endforeach
                                    </select>
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
                        </div>
                    </form>

                    <!-- Tabla de Empleados -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive small-font">
                                <table class="table table-bordered text-center table-striped" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th class="col-1 sortable">
                                                <a href="{{ route('users', ['sort_by' => 'username', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                    USUARIOS
                                                    @if(request('sort_by') == 'username')
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
                                            <th class="col-2 sortable">
                                                <a href="{{ route('users', ['sort_by' => 'employee_name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                    NOMBRE EMPLEADO
                                                    @if(request('sort_by') == 'employee_name')
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
                                            <th class="col-1 sortable">
                                                <a href="{{ route('users', ['sort_by' => 'role', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                    ROL
                                                    @if(request('sort_by') == 'role')
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
                                            <th class="col-1 sortable">
                                                <a href="{{ route('users', ['sort_by' => 'status', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
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
                                            <th class="col-4 sortable">
                                                <a href="{{ route('users', ['sort_by' => 'cost_center', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                    CENTRO DE COSTO
                                                    @if(request('sort_by') == 'cost_center')
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

                                            <th class="col-1">ACCIONES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users as $user)
                                        <tr>
                                            <td>{{ $user->username }}</td>
                                            <td>
                                                @if ($user->employee)
                                                {{ $user->employee->first_name }} {{ $user->employee->last_name }} {{ $user->employee->middle_name }}
                                                @else
                                                Ningún empleado asignado
                                                @endif
                                            </td>
                                            <td>
                                                @foreach($user->roles ?? [] as $role)
                                                {{ $role->name }}@if(!$loop->last), @endif
                                                @endforeach
                                            </td>
                                            <td>
                                                @if ($user->status == 1)
                                                <span class="badge bg-primary">ACTIVO</span>
                                                @else
                                                <span class="badge bg-danger">INACTIVO</span>
                                                @endif
                                            </td>
                                            <td>
                                                @foreach($user->costCenters as $center)
                                                {{ $center->cost_center_id }}@if(!$loop->last), @endif
                                                @endforeach
                                            </td>
                                            <td>
                                                <div class="d-inline-block">
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="resetPassword('{{ $user->id }}')">
                                                        <i class="fas fa-rotate-right"></i>
                                                    </button>
                                                </div>
                                                <!-- Modal de Edición de Usuario -->
                                                <div class="modal fade text-left" id="editUserModal{{ $user->id }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $user->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editUserModalLabel{{ $user->id }}">Editar Usuario</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form id="editUserForm{{ $user->id }}" method="POST" action="{{ route('users.update', $user->id) }}">
                                                                    @csrf
                                                                    @method('PUT')

                                                                    <!-- Campos del formulario -->
                                                                    <div class="mb-3">
                                                                        <label for="username" class="form-label">Usuario</label>
                                                                        <input type="text" class="form-control uper" id="username" name="username" value="{{ $user->username }}" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="employee_name" class="form-label">Empleado</label>
                                                                        <input type="text" class="form-control" id="employee_name" value="{{ $user->employee->first_name }} {{ $user->employee->last_name }} {{ $user->employee->middle_name }}" readonly>
                                                                        <input type="hidden" name="employee_id" value="{{ $user->employee_id }}">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Roles</label>
                                                                        <div class="row">
                                                                            @foreach($roles->chunk(4) as $chunk)
                                                                            <div class="col-md-3">
                                                                                @foreach($chunk as $role)
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input" type="checkbox" value="{{ $role->id }}" id="role{{ $role->id }}" name="roles[]" {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                                                                                    <label class="form-check-label" for="role{{ $role->id }}">
                                                                                        {{ $role->name }}
                                                                                    </label>
                                                                                </div>
                                                                                @endforeach
                                                                            </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Centro de Costo</label>
                                                                        <div class="row">
                                                                            @foreach($centers->chunk(4) as $chunk)
                                                                            <div class="col-md-3">
                                                                                @foreach($chunk as $center)
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input" type="checkbox" value="{{ $center->id }}" id="center{{ $center->id }}" name="centers[]" {{ $user->costCenters->contains($center->id) ? 'checked' : '' }}>
                                                                                    <label class="form-check-label" for="center{{ $center->id }}">
                                                                                        {{ $center->cost_center_id }}
                                                                                    </label>
                                                                                </div>
                                                                                @endforeach
                                                                            </div>
                                                                            @endforeach
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
                                                <!-- Fin Modal de Edición de Usuario -->
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $users->appends(request()->all())->links() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- End User Table -->
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->
        </div>
        <!-- End of Page Wrapper -->

        <!-- Modal para agregar usuario -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Agregar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addUserForm" method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="employee_id_add" class="form-label">Empleado</label>
                                <select class="form-select" id="employee_id_add" name="employee_id" required onchange="generateUsername()">
                                    <option value="" disabled selected>Selecciona un empleado</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" data-firstname="{{ $employee->first_name }}" data-lastname="{{ $employee->last_name }}">
                                        {{ $employee->first_name }} @if($employee->last_name){{ $employee->last_name }}@endif {{ $employee->middle_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="username_add" class="form-label">Usuario</label>
                                <input type="text" class="form-control uper" id="username_add" name="username" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="password_add" class="form-label">Contraseña</label>
                                <input type="text" class="form-control" id="password_add" name="password" value="Ferre01@" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="roles_add" class="form-label">Roles</label>
                                <div class="row">
                                    @foreach($roles->chunk(4) as $chunk)
                                    <div class="col-md-3">
                                        @foreach($chunk as $role)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $role->id }}" id="role{{ $role->id }}_add" name="roles[]">
                                            <label class="form-check-label" for="role{{ $role->id }}_add">{{ $role->name }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="center_add" class="form-label">Centro de Costo</label>
                                <div class="row">
                                    @foreach($centers->chunk(4) as $chunk)
                                    <div class="col-md-3">
                                        @foreach($chunk as $center)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $center->id }}" id="center{{ $center->id }}_add" name="center[]">
                                            <label class="form-check-label" for="center{{ $center->id }}_add">{{ $center->cost_center_id }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="submit" form="addUserForm" class="btn btn-primary" onclick="return validatePassword()">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fin Modal para agregar usuario -->

        <!-- Modal de Éxito -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="successModalLabel">Éxito</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Mensaje de éxito aquí.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fin Modal de Éxito -->

        <!-- Modal de Error -->
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="errorModalLabel">Error</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Mensaje de error aquí.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fin Modal de Error -->

        <!-- Modal de Confirmación -->
        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmationModalLabel">Confirmación requerida</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de restablecer la contraseña por defecto?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmResetButton">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fin Modal de Confirmación -->


        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
        <!-- Bootstrap core JavaScript -->
        <script src="assets/vendor/jquery/jquery.min.js"></script>
        <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
        <script src="assets/vendor/chart.js/Chart.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="{{ asset('js/users.js') }}"></script>
</body>

</html>