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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sb-admin-2.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body id="page-top" class="body">
    <!-- Page Wrapper -->
    <div id="wrapper">
        @include('slidebar')
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column dash">
            <!-- Main Content -->
            <div id="content">
                @include('navbar')
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <br><br>
                    <div class="welcome-message">
                        <p>Bienvenido, {{ $employee->first_name }} {{ $employee->last_name }}!</p>
                    </div>
                    <div class="employee-details">
                        <!-- Quick Access -->
                        <div class="row">

                            <!-- Notifications and Alerts -->
                            <div class="col-lg-4 col-md-12 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title">Notificaciones</h5>
                                        <p class="card-text">Aquí verás tus alertas y notificaciones importantes.</p>
                                        <!-- Display notifications dynamically -->
                                    </div>
                                </div>
                            </div>
                            <!-- Statistics and Performance -->
                            <div class="col-lg-4 col-md-12 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title">Estadísticas Personales</h5>
                                        <p class="card-text">Resumen de tu desempeño y progreso.</p>
                                        <!-- Display statistics dynamically -->
                                    </div>
                                </div>
                            </div>

                            <!-- Company News -->
                            <div class="col-lg-4 col-md-12 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title">Noticias de la Empresa</h5>
                                        <p class="card-text">Mantente al día con las últimas noticias y actualizaciones.
                                        </p>
                                        <!-- Display news dynamically -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.container-fluid -->
                </div>
                <!-- End of Main Content -->
            </div>
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Page Wrapper -->

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <!-- Modal -->
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="errorModalLabel">Error de Permisos</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        No tienes permiso para acceder a esta página.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
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

        <!-- Script para mostrar el modal de error -->
        <script>
            $(document).ready(function () {
                @if (session('error'))
                    $('#errorModal').modal('show');
                @endif
            });
        </script>
</body>

</html>