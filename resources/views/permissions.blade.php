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
                    <h1 class="mt-5" style="text-align: center;">PERMISOS</h1>
                    <!-- Add Employe Button -->
                    <div class="container">
                        <div class="row align-items-center justify-content-center mb-4">
                            <!-- Botón para agregar rol -->
                            <div class="col-md-2 mb-3">
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addPermissionModal" style="margin-top: 32px;">
                                    <i class="fas fa-plus-circle mr-2"></i>Agregar Permiso
                                </button>
                            </div>
                            <!-- Filtro buscar nombre -->
                            <div class="col-md-3 mb-3">
                                <form id="searchForm" action="{{ route('permissions') }}" method="GET" style="margin-top: 32px;">
                                    <div class="input-group">
                                        <input type="text" class="form-control uper" placeholder="Buscar permiso" id="searchPermission" name="search" value="{{ request('search') }}">
                                        <button class="btn btn-danger" type="button" onclick="limpiarCampos()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- /.container-fluid -->

                    <!-- Begin Page Content -->
                    <div class="container-fluid">

                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="col-1 text-center align-middle sortable">
                                                    <a href="{{ route('permissions', ['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        NOMBRE
                                                        @if(request('sort_by') == 'name')
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
                                                    <a href="{{ route('permissions', ['sort_by' => 'description', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        DESCRIPCIÓN
                                                        @if(request('sort_by') == 'description')
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
                                                <th class="col-1 text-center align-middle">ACCIONES</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @if($permissions->isNotEmpty())
                                            @foreach($permissions as $permission)
                                            <tr>
                                                <td class="col-1 text-center align-middle">{{ $permission->name }}</td>
                                                <td>{{ $permission->description }}</td>
                                                <td class="col-1 text-center align-middle">
                                                    <div class="d-inline-block">
                                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editPermissionModal{{ $permission->id }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                    <!-- Modal de Edición de Rol -->
                                                    <div class="modal fade text-left" id="editPermissionModal{{ $permission->id }}" tabindex="-1" aria-labelledby="editPermissionModalLabel{{ $permission->id }}" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="editPermissionModalLabel{{ $permission->id }}">Editar Permiso</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form id="editPermissionForm{{ $permission->id }}" method="POST" action="{{ route('permissions.update', $permission->id) }}">
                                                                        @csrf
                                                                        @method('PUT')

                                                                        <!-- Campos del formulario -->
                                                                        <div class="row mb-3">
                                                                            <div class="col-12">
                                                                                <label for="name" class="form-label">Permiso</label>
                                                                                <input type="text" class="form-control uper" id="name" name="name" value="{{ $permission->name }}" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-3">
                                                                            <div class="col-12">
                                                                                <label for="description" class="form-label">Descripción</label>
                                                                                <input type="text" class="form-control" id="description" name="description" value="{{ $permission->description }}" required>
                                                                            </div>
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
                                <!-- Fin Modal de Edición de Rol -->

                                </td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="4">@if($selectedDepartment) Ningún permiso registrado @else Seleccione un departamento para mostrar los permisos @endif</td>
                                </tr>
                                @endif
                                </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar permiso -->
    <div class="modal fade" id="addPermissionModal" tabindex="-1" aria-labelledby="addPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPermissionModalLabel">Agregar Permiso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPermissionForm" method="POST" action="{{ route('permissions.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre del Permiso</label>
                            <input type="text" class="form-control uper" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
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
    <!-- Fin Modal para agregar permiso -->

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
    <script src="{{ asset('js/permissions.js') }}"></script>
    </div>
</body>

</html>