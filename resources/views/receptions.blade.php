<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Detalles de Recepción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/sb-admin-2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body id="page-top">
    <div id="wrapper">
        @include('slidebar')
        <div id="content-wrapper" class="d-flex flex-column dash" style="overflow-y: hidden;">
            <div id="content">
                @include('navbar')
                <div class="container-fluid">
                    <h1 class="mt-5 text-center">Detalles de Recepción</h1>
                    <br>

                    <!-- Mensajes de error y éxito -->
                    @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                    @endif

                    @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    @endif

                    <div class="row g-3 align-items-end">
                        <br>
                        <form method="POST" action="{{ route('receiptOrder', $order->ACMVOIDOC) }}" id="receptionForm">
                            @csrf
                            <div class="row g-3 align-items-end">
                                <!-- Formulario de recepción -->
                                <div class="col-md-2">
                                    <label for="numero" class="form-label">Número:</label>
                                    <div class="input-group">
                                        <input type="text" id="numero" name="carrier_number" class="form-control" required>
                                        <button class="btn btn-danger btn-outline-light clear-input" type="button" id="clearNumero">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <ul id="numeroList" class="list-group" style="display: none;"></ul>
                                </div>
                                <div class="col-md-4">
                                    <label for="fletero" class="form-label">Fletero:</label>
                                    <div class="input-group">
                                        <input type="text" id="fletero" name="carrier_name" class="form-control" required>
                                        <button class="btn btn-danger btn-outline-light clear-input" type="button" id="clearFletero">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <ul id="fleteroList" class="list-group" style="display: none;"></ul>
                                </div>
                                <div class="col-md-1">
                                    <label for="tipo_doc" class="form-label">Tipo Doc:</label>
                                    <input type="text" id="tipo_doc" name="document_type" class="form-control" value="{{ $order->CNTDOCID }}" readonly required>
                                </div>
                                <div class="col-md-1">
                                    <label for="num_doc" class="form-label">No. de Doc:</label>
                                    <input type="text" id="num_doc" name="document_number" class="form-control" value="{{ $order->ACMVOIDOC }}" readonly required>
                                </div>
                                <div class="col-md-4">
                                    <label for="nombre_proveedor" class="form-label">Nombre del Proveedor:</label>
                                    <input type="text" id="nombre_proveedor" name="supplier_name" class="form-control" value="{{ $provider ? $provider->CNCDIRNOM : 'No disponible' }}" readonly required>
                                </div>
                                <div class="col-md-1">
                                    <label for="referencia" class="form-label">Referencia:</label>
                                    <select id="referencia" name="reference_type" class="form-control" required>
                                        <option value="1">FACTURA</option>
                                        <option value="2">REMISION</option>
                                        <option value="3">MISCELANEO</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label for="almacen" class="form-label">Almacén:</label>
                                    <input type="text" id="almacen" name="store" class="form-control" value="{{ $order->ACMVOIALID }}" readonly required>
                                </div>
                                <div class="col-md-1">
                                    <label for="ACMROIREF" class="form-label">Referencia:</label>
                                    <input type="text" id="ACMROIREF" name="reference" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="fecha" class="form-label">Fecha Recepción:</label>
                                    <input type="date" id="fecha" name="reception_date" class="form-control" value="{{ $currentDate }}" readonly required>
                                </div>
                                <div class="col-md-1">
                                    <label for="rcn_final" class="form-label">DOC:</label>
                                    <input type="text" id="rcn_final" name="document_type1" class="form-control" value="RCN" readonly required>
                                </div>
                                <div class="col-md-1">
                                    <label for="num_rcn_letras" class="form-label">NO DE DOC:</label>
                                    <input type="text" id="num_rcn_letras" name="document_number1" class="form-control" value="{{ $num_rcn_letras }}" readonly required>
                                </div>
                                <div class="col-md-1">
                                    <label for="flete_select" class="form-label">Flete:</label>
                                    <select id="flete_select" name="flete_select" class="form-select" onchange="toggleFleteInput()" required>
                                        <option value="0">Sin Flete</option>
                                        <option value="1">Con Flete</option>
                                    </select>
                                </div>
                                <div id="flete_input_div" class="col-md-1" style="display: none;">
                                    <label for="flete" class="form-label">Flete:</label>
                                    <input type="text" id="flete" name="freight" class="form-control" placeholder="Monto">
                                </div>

                                <div class="col-md-1">
                                    <a href="{{ route('orders') }}" class="btn btn-secondary">Regresar</a>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-warning">Recepcionar</button>
                                </div>
                            </div>
                            <br>

                            <input type="hidden" id="totalCost" name="total_cost" value="0">

                            <div class="table-responsive">
                                <div class="container-fluid">
                                    <div class="card shadow mb-4">
                                        <div class="card-body">
                                            <div class="table-responsive small-font">
                                                <table class="table table-bordered table-centered" id="dataTable" width="100%" cellspacing="0">
                                                    <thead>
                                                        <tr>
                                                            <th>LIN</th>
                                                            <th>ID</th>
                                                            <th>DESCRIPCION</th>
                                                            <th>SKU</th>
                                                            <th>UM</th>
                                                            <th>Cantidad Solicitada</th>
                                                            <th>Cantidad Recibida</th>
                                                            <th>Precio Unitario</th>
                                                            <th>IVA</th>
                                                            <th>Subtotal</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="receptionTableBody">
                                                        @foreach ($partidas as $partida)
                                                        <tr>
                                                            <td>{{ number_format($partida->ACMVOILIN) }}</td>
                                                            <td>{{ $partida->ACMVOIPRID }}</td>
                                                            <td>{{ $partida->ACMVOIPRDS }}</td>
                                                            <td>{{ $partida->ACMVOINPAR }}</td>
                                                            <td>{{ $partida->ACMVOIUMT }}</td>
                                                            <td>{{ number_format($partida->ACMVOIQTO, 2) }}</td>
                                                            <td>
                                                                <input type="number" class="form-control cantidad-recibida" name="cantidad_recibida[]" value="" step="1" min="0" max="{{ $partida->ACMVOIQTO }}" oninput="calculateTotals(this)">
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control precio-unitario" name="precio_unitario[]" value="{{ number_format($partida->ACMVOINPO, 2) }}" min="0" step="0.01" oninput="calculateTotals(this)" required>
                                                            </td>

                                                            <td>{{ number_format($partida->ACMVOIIVA, 2) }}</td>
                                                            <td class="subtotal">0.00</td>
                                                            <td class="total">0.00</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <br>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de Carga -->
   <!-- Modal de Carga -->
<div class="modal fade animate__animated animate__fadeIn" id="loadingModal" tabindex="-1" role="dialog" aria-labelledby="loadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loadingModalLabel">Procesando Recepción</h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-3">Por favor espera mientras procesamos la recepción.</p>
            </div>
            <div class="modal-footer d-none" id="modalFooter">
                <button type="button" class="btn btn-secondary" id="closeModalButton" data-bs-dismiss="modal">Salir</button>
                <button type="button" class="btn btn-primary d-none" id="goToOrdersButton">Regresar a Órdenes</button>
            </div>
        </div>
    </div>
</div>


    <!-- Carga de jQuery, Bootstrap y otros scripts desde CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{ asset('js/reception.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#receptionForm');
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    const closeModalButton = document.getElementById('closeModalButton');
    const goToOrdersButton = document.getElementById('goToOrdersButton');
    const modalFooter = document.getElementById('modalFooter');
    const spinner = document.querySelector('.spinner-border');
    const modalBodyText = document.querySelector('.modal-body p');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        loadingModal.show();

        const formData = new FormData(form);

        axios.post(form.action, formData)
            .then(response => {
                if (response.data.success) {
                    spinner.classList.add('d-none');
                    modalFooter.classList.remove('d-none');
                    closeModalButton.classList.remove('d-none');
                    goToOrdersButton.classList.remove('d-none');
                    modalBodyText.innerText = response.data.message;

                    goToOrdersButton.addEventListener('click', function() {
                        window.location.href = "{{ route('orders') }}";
                    });
                } else {
                    throw new Error(response.data.message || 'Error en la recepción de la orden.');
                }
            })
            .catch(error => {
                spinner.classList.add('d-none');
                modalFooter.classList.remove('d-none');
                closeModalButton.classList.remove('d-none');
                modalBodyText.innerText = error.message;
            });
    });
});

        function toggleFleteInput() {
            const fleteSelect = document.getElementById('flete_select');
            const fleteInputDiv = document.getElementById('flete_input_div');
            if (fleteSelect.value == '1') {
                fleteInputDiv.style.display = 'block';
            } else {
                fleteInputDiv.style.display = 'none';
            }
        }

        function calculateTotals(input) {
            const row = input.closest('tr');
            const cantidadRecibida = parseFloat(row.querySelector('.cantidad-recibida').value) || 0;
            const precioUnitario = parseFloat(row.querySelector('.precio-unitario').value) || 0;
            const iva = parseFloat(row.cells[8].innerText) || 0;

            const subtotal = cantidadRecibida * precioUnitario;
            const total = subtotal + (subtotal * (iva / 100));

            row.querySelector('.subtotal').innerText = subtotal.toFixed(2);
            row.querySelector('.total').innerText = total.toFixed(2);

            updateTotalCost();
        }

        function updateTotalCost() {
            let totalCost = 0;
            document.querySelectorAll('.total').forEach(totalCell => {
                totalCost += parseFloat(totalCell.innerText) || 0;
            });
            document.getElementById('totalCost').value = totalCost.toFixed(2);
        }
    </script>
</body>

</html>
