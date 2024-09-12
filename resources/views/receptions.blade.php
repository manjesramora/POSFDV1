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
                    <div class="row g-3 align-items-end justify-content-center filter-container">
                        <form method="POST" action="{{ route('receiptOrder', $order->ACMVOIDOC) }}" id="receptionForm">
                            @csrf
                            <div class="row g-3 align-items-end justify-content-center">
                                <!-- Formulario de recepción -->
                                <div class="col-md-2">
                                    <label for="flete_select" class="form-label">Flete</label>
                                    <select id="flete_select" name="flete_select" class="form-select" required>
                                        <option value="0">Sin Flete</option>
                                        <option value="1">Con Flete</option>
                                    </select>
                                </div>
                                <div id="flete_input_div" class="col-md-2" style="display: none;">
                                    <label for="flete" class="form-label">Monto Flete:</label>
                                    <input type="text" id="flete" name="freight" class="form-control input-no-spinner" placeholder="$0.00">
                                </div>
                                <div class="col-md-2" id="fletero_fields" style="display: none;">
                                    <label for="numero" class="form-label"># Fletero:</label>
                                    <div class="input-group">
                                        <input type="text" id="numero" name="carrier_number" class="form-control input-no-spinner" placeholder="Opcional">
                                        <button class="btn btn-danger btn-outline-light clear-input" type="button" id="clearNumero">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <ul id="numeroList" class="list-group" style="display: none;"></ul>
                                </div>
                                <div class="col-md-4" id="fletero_fields_name" style="display: none;">
                                    <label for="fletero" class="form-label">Nombre Fletero:</label>
                                    <div class="input-group">
                                        <input type="text" id="fletero" name="carrier_name" class="form-control input-no-spinner" placeholder="Opcional">
                                        <button class="btn btn-danger btn-outline-light clear-input" type="button" id="clearFletero">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <ul id="fleteroList" class="list-group" style="display: none;"></ul>
                                </div>
                                <div class="col-md-1">
                                    <label for="tipo_doc" class="form-label">Doc. Origen:</label>
                                    <input type="text" id="tipo_doc" name="document_type" class="form-control" value="{{ $order->CNTDOCID }}" readonly required>
                                </div>
                                <div class="col-md-2">
                                    <label for="num_doc" class="form-label"></label>
                                    <input type="text" id="num_doc" name="document_number" class="form-control" value="{{ $order->ACMVOIDOC }}" readonly required>
                                </div>
                                <div class="col-md-4">
                                    <label for="nombre_proveedor" class="form-label">Nombre del Proveedor:</label>
                                    <input type="text" id="nombre_proveedor" name="supplier_name" class="form-control" value="{{ $provider ? $provider->CNCDIRNOM : 'No disponible' }}" readonly required>
                                </div>
                                <div class="col-md-2">
                                    <label for="referencia" class="form-label">Tipo de Referencia:</label>
                                    <select id="referencia" name="reference_type" class="form-control" required>
                                        <option value="1">Factura</option>
                                        <option value="2">Remision</option>
                                        <option value="3">Miscelaneo</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="ACMROIREF" class="form-label">Referencia:</label>
                                    <input type="text" id="ACMROIREF" name="reference" class="form-control" required>
                                </div>
                                <div class="col-md-1">
                                    <label for="almacen" class="form-label">Almacen:</label>
                                    <input type="text" id="almacen" name="store" class="form-control" value="{{ $order->ACMVOIALID }}" readonly required>
                                </div>
                                <div class="col-md-2">
                                    <label for="fecha" class="form-label">Fecha Recepcion</label>
                                    <input type="date" id="fecha" name="reception_date" class="form-control" value="{{ $currentDate }}" readonly required>
                                </div>
                                <div class="col-md-2">
                                    <label for="rcn_final" class="form-label">Doc. Recepcion</label>
                                    <input type="text" id="rcn_final" name="document_type1" class="form-control" value="RCN" readonly required>
                                </div>
                                <div class="col-md-1">
                                    <label for="num_rcn_letras" class="form-label"></label>
                                    <input type="text" id="num_rcn_letras" name="document_number1" class="form-control" value="{{ $num_rcn_letras }}" readonly required>
                                </div>
                                <div class="col-md-12">
                                <div id="reception-error" class="alert alert-danger d-none text-center mx-auto">
                                    No es posible realizar la recepción si todas las Cantidades Recibidas son 0
                                </div>

                                </div>
                                <div class="col-md-2 d-flex">
                                    <a href="{{ route('orders') }}" class="btn btn-secondary me-2">Regresar</a>
                                    <button type="submit" class="btn btn-info">Recepcionar</button>
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
                                                        @foreach ($receptions as $index => $reception)
                                                        <tr>
                                                            <td>{{ (int)$reception->ACMVOILIN }}</td>
                                                            <td>{{ (int)$reception->ACMVOIPRID }}</td>
                                                            <td>{{ $reception->ACMVOIPRDS }}</td>
                                                            <td>{{ $reception->ACMVOINPAR }}</td>
                                                            <td>{{ $reception->ACMVOIUMT }}</td>
                                                            <td>{{ rtrim(rtrim(number_format($reception->ACMVOIQTP > 0 ? $reception->ACMVOIQTP : $reception->ACMVOIQTO, 4, '.', ''), '0'), '.') }}</td>
                                                            <td>
                                                                <input type="number" class="form-control cantidad-recibida input-no-spinner" name="cantidad_recibida[{{ $index }}]"
                                                                    value="" step="0.0001" min="0" max="{{ rtrim(rtrim(number_format($reception->ACMVOIQTP > 0 ? $reception->ACMVOIQTP : $reception->ACMVOIQTO, 4, '.', ''), '0'), '.') }}">
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control precio-unitario input-no-spinner" name="precio_unitario[{{ $index }}]"
                                                                    value="{{ rtrim(rtrim(number_format($reception->ACMVOINPO, 4, '.', ''), '0'), '.') }}" 
                                                                    data-original-value="{{ rtrim(rtrim(number_format($reception->ACMVOINPO, 4, '.', ''), '0'), '.') }}" required>
                                                            </td>
                                                            <td>{{ rtrim(rtrim(number_format($reception->ACMVOIIVA, 4, '.', ''), '0'), '.') }}</td>
                                                            <td class="subtotal">$0.00</td>
                                                            <td class="total">$0.00</td>
                                                            <input type="hidden" name="acmvoilin[{{ $index }}]" value="{{ (int)$reception->ACMVOILIN }}">
                                                            <input type="hidden" name="acmvoiprid[{{ $index }}]" value="{{ (int)$reception->ACMVOIPRID }}">
                                                            <input type="hidden" name="acmvoiprds[{{ $index }}]" value="{{ $reception->ACMVOIPRDS }}">
                                                            <input type="hidden" name="acmvoiumt[{{ $index }}]" value="{{ $reception->ACMVOIUMT }}">
                                                            <input type="hidden" name="acmvoiiva[{{ $index }}]" value="{{ rtrim(rtrim(number_format($reception->ACMVOIIVA, 4, '.', ''), '0'), '.') }}">
                                                            <input type="hidden" name="acmvoiqto[{{ $index }}]" value="{{ $reception->ACMVOIQTO }}">
                                                            <input type="hidden" name="acmvoiqtp[{{ $index }}]" value="{{ $reception->ACMVOIQTP }}">
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
    <div class="modal fade animate__animated animate__fadeIn" id="loadingModal" tabindex="-1" role="dialog" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
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
                    <button type="button" class="btn btn-secondary" id="closeModalButton" data-bs-dismiss="modal" disabled>Salir</button>
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
</body>
</html>
