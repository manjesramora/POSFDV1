<nav>
    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar" style="height: 100vh; position: fixed;">

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('index') }}" style="margin-top: 30px;" border-radius: 10px; overflow: hidden;">
            <img src="/assets/img/logos.png" style="max-width: 90px; height: auto;" margin>
        </a>

        <br>

        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Boton Empleados -->
        @if(auth()->user()->hasPermission('EMPLEADOS'))
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('employees') }}">
                <i class="fa fa-address-book" style="font-size: 16px; width: 30px;"></i>
                <span style="font-size: 16px;">EMPLEADOS</span>
            </a>
        </li>
        @endif

        <!-- Boton Usuarios -->
        @if(auth()->user()->hasPermission('USUARIOS'))
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('users') }}">
                <i class="fa-solid fa-user" style="font-size: 16px; width: 30px;"></i>
                <span style="font-size: 16px;">USUARIOS</span>
            </a>
        </li>
        @endif

        <!-- Boton Roles -->
        @if(auth()->user()->hasPermission('ROLES'))
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('roles') }}">
                <i class="fa-solid fa-user-check" style="font-size: 16px; width: 30px;"></i>
                <span style="font-size: 16px;">ROLES</span>
            </a>
        </li>
        @endif

        <!-- Boton Permisos -->
        @if(auth()->user()->hasPermission('PERMISOS'))
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('permissions') }}">
                <i class="fa-solid fa-list" style="font-size: 16px; width: 30px;"></i>
                <span style="font-size: 16px;">PERMISOS</span>
            </a>
        </li>
        @endif

        <!-- Boton Ordenes de compra -->
        @if(auth()->user()->hasPermission('ORDENES'))
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('orders') }}">
                <i class="fa fa-truck" style="font-size: 16px; width: 30px;"></i>
                <span style="font-size: 16px;">ORDENES DE COMPRA</span>
            </a>
        </li>
        @endif

        <!-- Boton Etiquetas y Catalogo -->
        @if(auth()->user()->hasPermission('ETIQUETAS'))
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('labelscatalog') }}">
                <i class="fa-solid fa-tag" style="font-size: 16px; width: 30px;"></i>
                <span style="font-size: 16px;">ETIQUETAS Y CATALOGO</span>
            </a>
        </li>
        @endif

        <!-- Boton Fletes -->
        @if(auth()->user()->hasPermission('FLETES'))
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('freights') }}">
                <i class="fa-solid fa-money-check-dollar" style="font-size: 16px; width: 30px;"></i>
                <span style="font-size: 16px;">FLETES</span>
            </a>
        </li>
        @endif

        <!-- Boton RCN -->
        @if(auth()->user()->hasPermission('RCN'))
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('rcn') }}">
                <i class="fa-solid fa-file-invoice" style="font-size: 16px; width: 30px;"></i>
                <span style="font-size: 16px;">RCN</span>
            </a>
        </li>
        @endif

        <!-- Divider -->
        <hr class="sidebar-divider">

    </ul>
    <!-- End of Sidebar -->
</nav>
