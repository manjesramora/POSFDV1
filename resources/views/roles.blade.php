<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>ADMIN DASH</title>
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
                    <h1 class="mt-5" style="text-align: center;">ROLES</h1>
                    <!-- Add Employe Button -->
                    <div class="container">
                        <div class="row align-items-center justify-content-center mb-4">
                            <!-- Botón para agregar rol -->
                            <div class="col-md-2 mb-3">
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addRoleModal" style="margin-top: 32px;">
                                    <i class="fas fa-plus-circle mr-2"></i>Agregar Rol
                                </button>
                            </div>
                            <!-- Filtro buscar nombre -->
                            <div class="col-md-3 mb-3">
                                <form id="searchForm" action="{{ route('roles') }}" method="GET" style="margin-top: 32px;">
                                    <div class="input-group">
                                        <input type="text" class="form-control uper" placeholder="Buscar rol" id="searchRole" name="search" value="{{ request('search') }}">
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

                        <!-- Role Table -->
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="table-responsive small-font">
                                    <table class="table table-bordered text-center table-striped" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="col-1 sortable">
                                                    <a href="{{ route('roles', ['sort_by' => 'role_name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        ROL
                                                        @if(request('sort_by') == 'role_name')
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
                                                    <a href="{{ route('roles', ['sort_by' => 'description', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        DESCRIPCION
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
                                                <th class="col-3 sortable">
                                                    <a href="{{ route('roles', ['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'] + request()->all()) }}">
                                                        PERMISOS
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
                                                <th class="col-1">ACCIONES</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach($roles as $role)
                                            <tr>
                                                <td>{{ $role->name }}</td>
                                                <td>{{ $role->description }}</td>
                                                <td>
                                                    @foreach($role->permissions ?? [] as $permission)
                                                    {{ $permission->name }}@if(!$loop->last), @endif
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <div class="d-inline-block">
                                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editRoleModal{{ $role->id }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Modal de Edición de Rol -->
                                                    <div class="modal fade text-left" id="editRoleModal{{ $role->id }}" tabindex="-1" aria-labelledby="editRoleModalLabel{{ $role->id }}" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="editRoleModalLabel{{ $role->id }}">Editar
                                                                        Rol</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form id="editRoleForm{{ $role->id }}" method="POST" action="{{ route('roles.update', $role->id) }}">
                                                                        @csrf
                                                                        @method('PUT')

                                                                        <!-- Campos del formulario -->
                                                                        <div class="row mb-3">
                                                                            <div class="col-12">
                                                                                <label for="name" class="form-label">Rol</label>
                                                                                <input type="text" class="form-control uper" id="name" name="name" value="{{ $role->name }}" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-3">
                                                                            <div class="col-12">
                                                                                <label for="description" class="form-label">Descripción</label>
                                                                                <input type="text" class="form-control" id="description" name="description" value="{{ $role->description }}" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-3">
                                                                            <div class="col-12">
                                                                                <label class="form-label">Permisos</label>
                                                                                <div class="row g-3">
                                                                                    @foreach($permissions as $permission)
                                                                                    <div class="col-md-3 col-sm-4 col-6">
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input" type="checkbox" value="{{ $permission->id }}" id="permission{{ $permission->id }}" name="permissions[]" {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                                                                            <label class="form-check-label" for="permission{{ $permission->id }}">
                                                                                                {{ $permission->name }}
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>


                                                                        <div class="modal-footer">
                                                                            <button type="submit" class="btn btn-primary">Guardar
                                                                                Cambios</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Fin Modal de Edición de Rol -->


                                                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que quieres eliminar este rol?');">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>

                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- End Role Table -->

                    </div>
                    <!-- /.container-fluid -->

                </div>
                <!-- End of Main Content -->

            </div>
            <!-- End of Page Wrapper -->

            <!-- Modal para agregar rol -->
            <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Agregar Rol</h5>
                            <button type="button" class="btn-close uper" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addRoleForm" method="POST" action="{{ route('roles.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="name" class="form-label">Rol</label>
                                    <input type="text" class="form-control uper" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descripción</label>
                                    <input type="text" class="form-control" id="description" name="description" required>
                                </div>
                                <div class="mb-3">
                                    <label for="permissions" class="form-label">Permisos</label>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row g-3">
                                                @foreach($permissions as $permission)
                                                <div class="col-md-4 col-sm-6 col-12">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="{{ $permission->id }}" id="permission{{ $permission->id }}" name="permissions[]">
                                                        <label class="form-check-label" for="permission{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                <div class="modal-footer">

                                    <button type="submit" form="addRoleForm" class="btn btn-primary" onclick="return validatePassword()">Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FIn Modal para agregar usuario -->

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
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
            <script src="{{ asset('js/roles.js') }}"></script>
</body>

</html>