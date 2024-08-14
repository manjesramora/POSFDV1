<!DOCTYPE html>
<html>
<head>
    <title>Etiqueta de Código de Barras</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .label-container {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 0.5cm; /* Ajusta según el .drs */
            box-sizing: border-box;
            width: 5.9cm; /* Ajusta según el .drs */
            height: 2.9cm; /* Ajusta según el .drs */
            margin: 0.1cm; /* Ajusta según el .drs */
            page-break-after: always;
        }

        .description-barcode-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Alinea la descripción al inicio (izquierda) */
            width: 100%;
        }

        .description {
            font-size: 10px; /* Ajusta según el .drs */
            font-weight: bold;
            margin-bottom: 0.2cm; /* Ajusta según el .drs */
            width: auto;
        }

        .barcode-sku-container {
            display: flex;
            flex-direction: column;
            align-items: center; /* Alinea el código de barras y el SKU al centro */
            justify-content: center;
            width: 100%;
            margin-top: 0.2cm; /* Añade un poco de espacio superior si es necesario */
        }

        .barcode {
            margin-bottom: 0.1cm; /* Ajusta el espacio entre el código de barras y el SKU */
            text-align: center;
            width: 100%;
        }

        .sku {
            font-size: 12px; /* Ajusta según el .drs */
           
            font-weight: bold;
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
                    {!! $label['barcode'] !!}  <!-- Aquí permanece el código de barras real -->
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
