<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Etiquetas y Catalogo</title>
    <!-- Bootstrap core CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sb-admin-2.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body id="page-top">
    <div id="wrapper">
        @include('slidebar')
        <div id="content-wrapper" class="d-flex flex-column dash" style="overflow-y: hidden;">
            <div id="content">
                @include('navbar')
                <div class="container-fluid mt-4">
                    <div class="input-container mb-3 filtro">
                        <div class="row justify-content-center">
                            <div class="col-md-1">
                                <label for="productId" class="form-label">PRODUCTO</label>
                                <input type="text" name="productId" id="productId" class="form-control" maxlength="5" pattern="\d*" value="{{ request('productId') }}" oninput="validateInput(this, 5)">
                            </div>
                            <div class="col-md-1">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" name="sku" id="sku" class="form-control" maxlength="7" pattern="\d*" value="{{ request('sku') }}" oninput="validateInput(this, 7)">
                            </div>
                            <div class="col-md-3">
                                <label for="name" class="form-label">DESCRIPCIÓN</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ request('name') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="linea" class="form-label">LINEA</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="linea-addon">LN</span>
                                    <input type="text" name="linea" id="linea" class="form-control" maxlength="5" pattern="\d*" value="{{ request('linea') ? str_replace('LN', '', request('linea')) : '' }}" aria-describedby="linea-addon" oninput="validateInput(this, 5)">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label for="sublinea" class="form-label">SUBLINEA</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="sublinea-addon">SB</span>
                                    <input type="text" name="sublinea" id="sublinea" class="form-control" maxlength="7" pattern="\d*" value="{{ request('sublinea') ? str_replace('SB', '', request('sublinea')) : '' }}" aria-describedby="sublinea-addon" oninput="validateInput(this, 7)">
                                </div>
                            </div>

                            <div class="col-md-1">
                                <label for="departamento" class="form-label">DPTO</label>
                                <input type="text" name="departamento" id="departamento" class="form-control" maxlength="3" pattern="\d*" value="{{ request('departamento') }}" oninput="validateInput(this, 3)">
                            </div>
                            <div class="col-md-1">
                                <label for="activo" class="form-label">ACTIVOS</label>
                                <select name="activo" id="activo" class="form-control">
                                    <option value="todos" {{ request('activo') == 'todos' ? 'selected' : '' }}>Todos</option>
                                    <option value="activos" {{ request('activo') == 'activos' ? 'selected' : '' }}>Activos</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-primary me-2" onclick="buscarFiltros()">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">
                                    <i class="fas fa-eraser"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                                <!-- Tabla de datos -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive small-font">
                                <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            @php
                                            $columns = [
                                                'INPROD.INPRODID' => 'PRODUCTO',
                                                'INPROD.INPRODDSC' => 'DESCRIPCIÓN',
                                                'INPROD.INPRODI2' => 'SKU',
                                                'INSDOS.INSDOSQDS' => 'EXI',
                                                'INPROD.INPR02ID' => 'DPTO',
                                                'INPROD.INPRODCBR' => 'CODIGO BARRAS',
                                                'INPROD.INPR03ID' => 'LINEA',
                                                'INPROD.INPR04ID' => 'SUBLINEA',
                                                'INSDOS.INALMNID' => 'ALMACÉN',
                                                'PrecioBase' => 'PRECIO BASE',
                                                'AlmacenPrecio' => 'A/P'
                                            ];
                                            @endphp
                                            @foreach ($columns as $column => $label)
                                            <th>
                                                <a href="{{ route('labelscatalog', array_merge(request()->query(), ['sort' => $column, 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="sortable-column">
                                                    {{ $label }}
                                                    @if (request('sort') === $column)
                                                    @if (request('direction') === 'asc')
                                                    <i class="sort-icon fas fa-sort-up"></i>
                                                    @else
                                                    <i class="sort-icon fas fa-sort-down"></i>
                                                    @endif
                                                    @else
                                                    <i class="sort-icon fas fa-sort"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            @endforeach
                                            <th>ACCIONES</th>
                                        </tr>
                                    </thead>
                                    <tbody id="proveedorTable">
                                        @foreach($labels as $label)
                                        <tr>
                                            <td>{{ $label->INPRODID }}</td>
                                            <td>{{ $label->INPRODDSC }}</td>
                                            <td>{{ $label->INPRODI2 }}</td>
                                            <td>{{ number_format($label->Existencia, 2) }}</td>
                                            <td>{{ $label->INPR02ID }}</td>
                                            <td>{{ $label->INPRODCBR }}</td>
                                            <td>{{ $label->INPR03ID }}</td>
                                            <td>{{ $label->INPR04ID }}</td>
                                            <td>{{ $label->CentroCostos }}</td>
                                            <td>{{ $label->PrecioBase }}</td>
                                            <td>{{ $label->AlmacenPrecio }}</td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Acciones">
                                                    <button class="btn btn-secondary" onclick="showPrintModal('{{ $label->INPRODI2 }}', '{{ $label->INPRODDSC }}')" style="margin-right: 10px;">SKU
                                                        <i class="fas fa-barcode"></i>
                                                    </button>
                                                    <button class="btn btn-primary" onclick="showPrintModalWithPrice('{{ $label->INPRODI2 }}', '{{ $label->INPRODDSC }}', '{{ $label->PrecioBase }}')">SKU Y PRECIO
                                                        <i class="fas fa-tag"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-center">
                                    <div id="pagination-links">
                                        @if ($labels instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                        {{ $labels->appends(request()->query())->links() }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


    <!-- Modal para seleccionar la cantidad de etiquetas a imprimir -->

    <div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printModalLabel">Imprimir Etiquetas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="printForm" method="POST">
                        @csrf
                        <input type="hidden" name="sku" id="modalSku">
                        <input type="hidden" name="description" id="modalDescription">
                        <div class="form-group">
                            <label for="quantity">Cantidad de etiquetas</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="1">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="submitPrintForm()">Imprimir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar la cantidad de etiquetas a imprimir con SKU y Precio -->
<div class="modal fade" id="printModalWithPrice" tabindex="-1" aria-labelledby="printModalWithPriceLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printModalWithPriceLabel">Imprimir Etiquetas con Precio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="printFormWithPrice" method="POST">
                    @csrf
                    <input type="hidden" name="sku" id="modalSkuWithPrice">
                    <input type="hidden" name="description" id="modalDescriptionWithPrice">
                    <input type="hidden" name="precioBase" id="modalPrecioBase">
                    <div class="form-group">
                        <label for="quantityWithPrice">Cantidad de etiquetas</label>
                        <input type="number" name="quantity" id="quantityWithPrice" class="form-control" min="1" value="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="submitPrintFormWithPrice()">Imprimir</button>
            </div>
        </div>
    </div>
</div>
    <!-- Bootstrap core JavaScript -->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/vendor/chart.js/Chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/label.js') }}"></script>

    <script>
        var printLabelUrl = "{{ route('print.label') }}";
        var printLabelUrlWithPrice = "{{ route('print.label.with.price') }}";
    </script>
</body>

</html>