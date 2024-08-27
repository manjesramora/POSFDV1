<!DOCTYPE html>
<html>

<head>
    <title>Etiqueta de Código de Barras</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Calibri', sans-serif;
        }

        .label-container {
            transform: rotate(90deg) translateY(2.3cm) translateX(-0.2cm) scale(0.8);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 0.5cm;
            box-sizing: border-box;
            width: 5.9cm;
            height: 2.9cm;
            margin: 0;
            page-break-after: always;
        }

        .description-barcode-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }

        .description {
            font-size: 10px;
            margin-bottom: 0.2cm;
            white-space: normal;
            width: 100%;
            max-width: 100%;
            text-align: left;
            word-wrap: break-word;
            hyphens: auto;
            font-style: bold;
        }

        .barcode-sku-container {
            display: flex;
            flex-direction: column;
            align-items: center; /* Alinea todo al centro */
            justify-content: center;
            width: 100%;
            margin-top: 0.2cm;
        }

        .barcode {
            margin-bottom: 0.1cm;
            text-align: center;
            max-width: 100%; /* Asegura que el código de barras no exceda el ancho del contenedor */
        }

        .sku {
            font-size: 14px;
            text-align: center;
            font-weight: bold;
            margin-top: -0.1cm;
            margin-left: -1cm;
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
            </div>
        </div>
    </div>
    @endforeach
</body>

</html>
