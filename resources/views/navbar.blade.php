<!-- Topbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light topbar mb-4 static-top shadow fixed-top nava">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- User Information - Left Side -->
    <!-- User Information - Left Side -->
    <ul class="navbar-nav mr-auto">
        <li class="nav-item">
            <span id="role" class="nav-link text-gray-600 h3">
                @if(isset($userRoles) && count($userRoles) > 0)
                @foreach ($userRoles as $role)
                {{ $role->name }} <!-- Muestra el nombre del rol -->
                @if (!$loop->last)
                , <!-- Agrega una coma si no es el último rol -->
                @endif
                @endforeach
                @else
                Sin roles asignados <!-- Maneja el caso en el que el usuario no tenga roles -->
                @endif
            </span>
        </li>
        <li class="nav-item">
            <span id="role" class="nav-link text-gray-600 h3">|</span>
        </li>
        <li class="nav-item">
            <span id="datetime" class="nav-link text-gray-600 h3">Date and Time</span>
        </li>
    </ul>
    <!-- Topbar Navbar - Right Side -->
    <ul class="navbar-nav ml-auto">
        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color: #6e6e6e;
    font-weight: bold; /* Letra gruesa */
    font-size: 1.5em;"> <!-- Estilo en línea para color gris oscuro -->
                <!-- Mostrar el nombre del usuario si está autenticado -->
                @if(Auth::check())
                {{ Auth::user()->username }}
                @else
                Invitado
                @endif
                <!-- Agregar un espacio para separar el nombre del usuario de la flecha -->
                <span class="ml-1"><i class="fas fa-chevron-down"></i></span>
            </a>

            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Configuración
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Cerrar sesión
                </a>
            </div>
        </li>
    </ul>
</nav>
<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">¿Listo para salir?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Seleccione "Cerrar sesión" a continuación si está listo para cerrar su sesión actual.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                <form id="logout-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Topbar -->
<script>
    function updateDateTime() {
        const now = new Date();
        const formattedDateTime = now.toLocaleString(); // Formato de fecha y hora local
        document.getElementById('datetime').textContent = formattedDateTime;
    }

    // Actualizar la fecha y hora cada segundo (1000 ms)
    setInterval(updateDateTime, 1000);

    // Actualizar la fecha y hora al cargar la página
    updateDateTime();
</script>