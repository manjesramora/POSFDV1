<nav>
    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar" style="height: 100vh; position: fixed;">

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('index') }}" style="margin-top: 30px;" border-radius: 10px; overflow: hidden;">
            <img src="/assets/img/LP.png" style="max-width: 90px; height: auto;" margin>
        </a>

        <br>

        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Boton Empleados -->
        @if(auth()->user()->hasPermission('EMPLEADOS'))
        <li class="nav-item">
            <a class="nav-link" href="{{ route('employees') }}">
                <i class="fa fa-address-book"></i>
                <span>Empleados</span>
            </a>
        </li>
        @endif

        <!-- Boton Usuarios -->
        @if(auth()->user()->hasPermission('USUARIOS'))
        <li class="nav-item">
            <a class="nav-link" href="{{ route('users') }}">
                <i class="fa-solid fa-user"></i>
                <span>Usuarios</span>
            </a>
        </li>
        @endif

        <!-- Boton Roles -->
        @if(auth()->user()->hasPermission('ROLES'))
        <li class="nav-item">
            <a class="nav-link" href="{{ route('roles') }}">
                <i class="fa-solid fa-user-check"></i>
                <span>Roles</span>
            </a>
        </li>
        @endif

        <!-- Boton Permisos -->
        @if(auth()->user()->hasPermission('PERMISOS'))
        <li class="nav-item">
            <a class="nav-link" href="{{ route('permissions') }}">
                <i class="fa-solid fa-list"></i>
                <span>Permisos</span>
            </a>
        </li>
        @endif

        <!-- Boton Ordenes de compra -->
        @if(auth()->user()->hasPermission('ORDENES'))
        <li class="nav-item">
            <a class="nav-link" href="{{ route('orders') }}">
                <i class="fa fa-truck"></i>
                <span>Ordenes de Compra</span>
            </a>
        </li>
        @endif



        <!-- Boton Etiquetas y Catalogo -->
        @if(auth()->user()->hasPermission('ETIQUETAS'))
        <li class="nav-item">
            <a class="nav-link" href="{{ route('labelscatalog') }}">
                <i class="fa-solid fa-tag"></i>
                <span>Etiquetas y Catalogo</span>
            </a>
        </li>
        @endif

        <!-- Divider -->
        <hr class="sidebar-divider">

    </ul>
    <!-- End of Sidebar -->

</nav>