<!DOCTYPE html>
<html>
<head>
    <title>Etiqueta de Código de Barras y Precio</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .label-container {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 5px;
            box-sizing: border-box;
            width: 6cm;
            height: 3cm;
            margin: 5px;
            page-break-after: always;
        }

        .description-barcode-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }

        .description {
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
            width: 100%;
            margin-left: 24px; /* Ajusta este valor según sea necesario */
        }

        .barcode-sku-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .barcode {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .barcode img, .barcode div {
            display: block;
            margin: 0 auto;
        }

        .sku {
            font-size: 16px;
            text-align: center;
            font-weight: bold;
        }

        .precio-base {
            font-size: 14px;
            text-align: center;
            font-weight: bold;
            color: red;
        }
    </style>
</head>
<body>
    @foreach ($labels as $label)
    <div class="label-container">
        <div class="description-barcode-container">
            <div class="description">
                {{ $label['description'] }}
            </div>
            <div class="barcode-sku-container">
                <div class="barcode">
                    {!! $label['barcode'] !!}
                </div>
                <div class="sku">
                    {{ $label['sku'] }}
                </div>
                <div class="precio-base">
                    ${{ $label['precioBase'] }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
</body>
</html>
