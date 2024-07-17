
    function toggleFleteInput() {
        var selectBox = document.getElementById("flete_select");
        var selectedValue = selectBox.options[selectBox.selectedIndex].value;
        var fleteInputDiv = document.getElementById("flete_input_div");

        if (selectedValue === "1") {
            fleteInputDiv.style.display = "block";
        } else {
            fleteInputDiv.style.display = "none";
        }
    }

    // Autocompletado para el campo Número
    $("#numero").on("input", function () {
        let query = $(this).val();

        if (query.length >= 3) {
            $.ajax({
                url: "/providers/autocomplete",
                type: "GET",
                data: {
                    query: query,
                    field: "CNCDIRID",
                },
                success: function (data) {
                    let dropdown = $("#numeroList");
                    dropdown.empty().show();

                    data.forEach((item) => {
                        dropdown.append(
                            `<li class="list-group-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}">${item.CNCDIRID} - ${item.CNCDIRNOM}</li>`
                        );
                    });
                },
            });
        } else {
            $("#numeroList").hide();
        }
    });
    $(document).on("click", "#numeroList li", function () {
        let id = $(this).data("id");
        let name = $(this).data("name");
        $("#numero").val(id);
        $("#fletero").val(name);
        $("#numeroList").hide();
    });

    $("#clearNumero").on("click", function () {
        $("#numero").val("");
        $("#fletero").val("");
        $("#numeroList").hide();
    });

    // Autocompletado para el campo Fletero
    $("#fletero").on("input", function () {
        let query = $(this).val();

        if (query.length >= 3) {
            $.ajax({
                url: "/providers/autocomplete",
                type: "GET",
                data: {
                    query: query,
                    field: "CNCDIRNOM",
                },
                success: function (data) {
                    let dropdown = $("#fleteroList");
                    dropdown.empty().show();

                    data.forEach((item) => {
                        dropdown.append(
                            `<li class="list-group-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}">${item.CNCDIRID} - ${item.CNCDIRNOM}</li>`
                        );
                    });
                },
            });
        } else {
            $("#fleteroList").hide();
        }
    });

    $(document).on("click", "#fleteroList li", function () {
        let id = $(this).data("id");
        let name = $(this).data("name");
        $("#fletero").val(name);
        $("#numero").val(id);
        $("#fleteroList").hide();
    });

    $("#clearFletero").on("click", function () {
        $("#fletero").val("");
        $("#numero").val("");
        $("#fleteroList").hide();
    });

    function limitCantidad(input) {
        const max = parseFloat(input.getAttribute("max"));
        const value = parseFloat(input.value);
        if (value > max) {
            input.value = max;
        }
        distributeFreight();
    }

    function limitPrecio(input) {
        const max = parseFloat(input.getAttribute("max"));
        const value = parseFloat(input.value);
        if (value > max) {
            input.value = max;
        }
        distributeFreight();
    }

     // Función para formatear el número con separadores de miles
     function formatNumberWithCommas(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Función para actualizar el valor formateado
    function updateFormattedValue() {
        let input = document.getElementById('flete');
        let value = input.value.replace(/[^\d.]/g, ''); // Eliminar caracteres no numéricos excepto puntos
        let parts = value.split('.'); // Dividir por el punto decimal
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ","); // Formatear parte entera con comas
        
        // Volver a unir los valores con el punto decimal
        input.value = parts.length > 1 ? parts.join('.') : parts[0];
    }

    // Escuchar cambios en el input y actualizar el valor formateado
    let input = document.getElementById('flete');
    input.addEventListener('input', updateFormattedValue);

    