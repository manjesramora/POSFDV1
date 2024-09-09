$(document).ready(function () {
    // Autocompletado para proveedores
    $("#CNCDIRNOM").on("input", function () {
        let query = $(this).val();

        if (query.length >= 3) {
            $.ajax({
                url: "/providers/autocomplete",
                type: "GET",
                data: { query: query, field: "CNCDIRNOM" },
                success: function (data) {
                    let dropdown = $("#nameDropdown");
                    dropdown.empty().show();

                    data.forEach((item, index) => {
                        dropdown.append(
                            `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}" tabindex="${index}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                        );
                    });

                    dropdown.find(".dropdown-item").first().addClass("active");
                },
            });
        } else {
            $("#nameDropdown").hide();
        }
    });

    // Selección de un proveedor del dropdown
    $(document).on("click", ".dropdown-item", function () {
        let item = $(this);
        $("#CNCDIRNOM").val(item.data("name"));
        $("#nameDropdown").hide();
    });

    // Clear all filter input fields and reload the page
    window.limpiarCampos = function () {
        window.location.href = "/rcn"; // Redirigir a la URL original sin parámetros
    };
});
