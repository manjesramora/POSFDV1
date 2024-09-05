$(document).ready(function () {
    // Autocompletado por ID y por Nombre
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

                    // Llenar el dropdown con las opciones obtenidas
                    data.forEach((item, index) => {
                        dropdown.append(
                            `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}" tabindex="${index}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                        );
                    });

                    // Posicionar el dropdown justo debajo del input
                    let inputOffset = $("#CNCDIRNOM").offset();
                    let inputHeight = $("#CNCDIRNOM").outerHeight();

                    // Cambia la posición del dropdown usando top y left
                    dropdown.css({
                        position: "absolute",
                        top: inputOffset.top + inputHeight + "px", // Justo debajo del input
                        left: inputOffset.left + "px", // Alineado con el input
                        width: $("#CNCDIRNOM").outerWidth() + "px", // Del mismo tamaño que el input
                    });

                    // Hacer scroll dentro del dropdown si es necesario
                    dropdown
                        .css("max-height", "200px")
                        .css("overflow-y", "auto");

                    // Focus en el primer ítem
                    dropdown.find(".dropdown-item").first().addClass("active");
                },
            });
        } else {
            $("#nameDropdown").hide();
        }
    });

    // Selección de un proveedor del dropdown
    $(document).on("click", ".dropdown-item", function () {
        selectProvider($(this));
    });

    // Function to select a provider
    function selectProvider(item) {
        let name = item.data("name");
        $("#CNCDIRNOM").val(name);
        $("#nameDropdown").hide();
    }

    // Clear all filter input fields and reload the page
    window.limpiarCampos = function () {
        window.location.href = "/freights"; // Redirigir a la URL original sin parámetros
    };
});
