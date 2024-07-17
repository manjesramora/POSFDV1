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
                     <div class="row g-3 align-items-end">
                         <br>
                         <form method="POST" action="{{ route('receiptOrder', $order->ACMVOIDOC) }}">
    @csrf
    <div class="row g-3 align-items-end">
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
                <option value="3">MISELANEO</option>
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
            <label for="fecha" class="form-label">Fecha Recepcion:</label>
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
            <input type="text" id="flete" name="cost" class="form-control" placeholder="Monto">
        </div>
        <div class="col-md-2">
            <a href="{{ route('orders') }}" class="btn btn-secondary me-2">Volver a Órdenes</a>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-warning">Recepcionar</button>
        </div>
    </div>
    <br>
</form>


                     </div>
                     <br>
                     <div class="table-responsive">
                         <div class="container-fluid">
                             <div class="card shadow mb-4">
                                 <div class="card-body">
                                     <div class="table-responsive small-font">
                                         <table class="table table-bordered table-centered" id="dataTable" width="100%" cellspacing="0">
                                             <thead>
                                                 <tr>
                                                     <th class="col-md-0">LIN</th>
                                                     <th class="col-md-0">ID</th>
                                                     <th class="col-md-6">DESCRIPCION</th>
                                                     <th class="col-md-0">SKU</th>
                                                     <th class="col-md-0">UM</th>
                                                     <th class="col-md-0">Cantidad <br> Solicitada</th>
                                                     <th class="col-md-1">Cantidad <br> Recibida</th>
                                                     <th class="col-md-0">Precio Unitario</th>
                                                     <th class="col-md-0">IVA</th>
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
                                                         <input type="number" class="form-control cantidad-recibida" name="cantidad_recibida[]" value="" step="1" min="0" max="{{ $partida->ACMVOIQTO }}" oninput="limitCantidad(this)" onkeydown="return event.key !== '.' && event.key !== 'e' && event.key !== '+' && event.key !== '-';">
                                                     </td>
                                                     <td>
                                                         <input type="number" class="form-control precio-unitario" name="precio_unitario[]" value="{{ number_format($partida->ACMVOINPO, 2) }}" min="0" max="{{ number_format($partida->ACMVOINPO, 2) }}" step="0.01" oninput="limitPrecio(this)">
                                                     </td>
                                                     <td>{{ number_format($partida->ACMVOIIVA, 2) }}</td>
                                                 </tr>
                                                 @endforeach
                                             </tbody>
                                         </table>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
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
     <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
     <script src="{{ asset('js/reception.js') }}"></script>
 </body>

 </html>