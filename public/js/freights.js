$(document).ready(function () {
    // Autocompletado para proveedores
    $("#CNCDIRNOM").on("input", function () {
        let query = $(this).val();

        if (query.length >= 3) {
            $.ajax({
                url: "/providers/autocomplete",
                type: "GET",
                data: { query: query, field: "CNCDIRNOM", type: 'provider' }, // Usar 'provider' para identificar el tipo
                success: function (data) {
                    let dropdown = $("#nameDropdown");
                    dropdown.empty().show();

                    data.forEach((item, index) => {
                        if (item.CNCDIRID.startsWith("3")) {
                            dropdown.append(
                                `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}" tabindex="${index}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                            );
                        }
                    });

                    dropdown.find(".dropdown-item").first().addClass("active");
                },
            });
        } else {
            $("#nameDropdown").hide();
        }
    });

    // Autocompletado para transportistas
    $("#CNCDIRNOM_TRANSP").on("input", function () {
        let query = $(this).val();

        if (query.length >= 3) {
            $.ajax({
                url: "/providers/autocomplete",
                type: "GET",
                data: { query: query, field: "CNCDIRNOM", type: 'transporter' }, // Usar 'transporter' para diferenciar
                success: function (data) {
                    let dropdown = $("#transporterDropdown");
                    dropdown.empty().show();

                    data.forEach((item, index) => {
                        if (item.CNCDIRID.startsWith("4")) {
                            dropdown.append(
                                `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}" tabindex="${index}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                            );
                        }
                    });

                    dropdown.find(".dropdown-item").first().addClass("active");
                },
            });
        } else {
            $("#transporterDropdown").hide();
        }
    });

    // Selección de un proveedor del dropdown
    $(document).on("click", ".dropdown-item", function () {
        let item = $(this);
        if (item.closest("#nameDropdown").length) {
            $("#CNCDIRNOM").val(item.data("name"));
        } else if (item.closest("#transporterDropdown").length) {
            $("#CNCDIRNOM_TRANSP").val(item.data("name"));
        }
        $("#nameDropdown, #transporterDropdown").hide();
    });

    // Clear all filter input fields and reload the page
    window.limpiarCampos = function () {
        window.location.href = "/freights"; // Redirigir a la URL original sin parámetros
    };
});
