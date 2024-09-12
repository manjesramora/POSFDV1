$(document).ready(function () {
    // Establecer las fechas máximas y mínimas
    var today = new Date();
    var sixMonthsAgo = new Date();
    sixMonthsAgo.setMonth(today.getMonth() - 6);

    // Formato de fecha YYYY-MM-DD
    function formatDate(date) {
        var dd = String(date.getDate()).padStart(2, '0');
        var mm = String(date.getMonth() + 1).padStart(2, '0'); // Enero es 0
        var yyyy = date.getFullYear();
        return yyyy + '-' + mm + '-' + dd;
    }

    // Establecer fecha mínima como hace 6 meses y fecha máxima como hoy
    $('#start_date').attr('min', formatDate(sixMonthsAgo));
    $('#end_date').attr('max', formatDate(today));

    // Validar las fechas y asignar fecha actual si el campo "Hasta" está vacío
    $("#combinedForm").on("submit", function() {
        var startDate = $("#start_date").val();
        var endDate = $("#end_date").val();

        // Obtener la fecha actual en formato YYYY-MM-DD
        var currentDate = formatDate(today);

        // Si hay una fecha de inicio pero no una fecha de fin, asigna la fecha actual como fin
        if (startDate && !endDate) {
            $("#end_date").val(currentDate);
        }
    });

    // Autocompletado por ID y por Nombre
    $("#CNCDIRID, #CNCDIRNOM").on("input", function () {
        let query = $(this).val();
        let field = $(this).attr("id") === "CNCDIRID" ? "CNCDIRID" : "CNCDIRNOM";

        if (query.length >= 3) {
            $.ajax({
                url: "/providers/autocomplete",
                type: "GET",
                data: { query: query, field: field, screen: 'orders' }, // Enviar 'screen: orders' para identificar la pantalla
                success: function (data) {
                    let dropdown = field === "CNCDIRID" ? $("#idDropdown") : $("#nameDropdown");
                    dropdown.empty().show();

                    data.forEach((item, index) => {
                        // Solo mostrar proveedores cuyo CNCDIRID comience con "3"
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
            $("#idDropdown, #nameDropdown").hide();
        }
    });

    // Selección de un proveedor del dropdown
    $(document).on("click", ".dropdown-item", function () {
        selectProvider($(this));
    });

    // Handle keyboard navigation for dropdown items
    $("#CNCDIRID, #CNCDIRNOM").on("keydown", function (e) {
        let dropdown = $(this).attr("id") === "CNCDIRID" ? $("#idDropdown") : $("#nameDropdown");

        if (dropdown.is(":visible")) {
            let activeItem = dropdown.find(".dropdown-item.active");

            if (e.key === "ArrowDown") {
                e.preventDefault();
                let nextItem = activeItem.next(".dropdown-item");
                if (nextItem.length > 0) {
                    activeItem.removeClass("active");
                    nextItem.addClass("active");
                }
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                let prevItem = activeItem.prev(".dropdown-item");
                if (prevItem.length > 0) {
                    activeItem.removeClass("active");
                    prevItem.addClass("active");
                }
            } else if (e.key === "Enter") {
                e.preventDefault();
                selectProvider(activeItem);
            } else if (e.key === "Escape") {
                dropdown.hide();
            }
        }
    });

    // Function to select a provider
    function selectProvider(item) {
        let id = item.data("id");
        let name = item.data("name");
        $("#CNCDIRID").val(id);
        $("#CNCDIRNOM").val(name);
        $("#idDropdown, #nameDropdown").hide();
    }

    // Limpiar campos adicionales
    function limpiarCamposAdicionales() {
        document.getElementById("ACMVOIDOC").value = "";
        document.getElementById("CNCDIRID").value = "";
        document.getElementById("CNCDIRNOM").value = "";
        document.getElementById("start_date").value = "";
        document.getElementById("end_date").value = "";
        $("#idDropdown, #nameDropdown").hide();
    }

    // Clear all filter input fields and reload the page
    window.limpiarCampos = function() {
        window.location.href = "/orders"; // Redirigir a la URL original sin parámetros
    }
});


document.addEventListener('DOMContentLoaded', function () {
    // Seleccionar los campos
    var acmvoidocInput = document.getElementById('ACMVOIDOC');
    var cncdidInput = document.getElementById('CNCDIRID');

    // Función que filtra las teclas permitidas
    function validarNumeros(event) {
        let key = event.key;
        // Permitir solo números y controlar las teclas permitidas
        if (["e", "E", "+", "-", ".", ",", "*", "/"].includes(key)) {
            event.preventDefault(); // Bloquear la tecla
        }
    }

    // Asociar la función a los eventos 'keydown' para ambos campos
    acmvoidocInput.addEventListener('keydown', validarNumeros);
    cncdidInput.addEventListener('keydown', validarNumeros);

    // También evitamos que se peguen caracteres inválidos
    acmvoidocInput.addEventListener('paste', function (event) {
        let paste = (event.clipboardData || window.clipboardData).getData('text');
        if (isNaN(paste)) {
            event.preventDefault();
        }
    });

    cncdidInput.addEventListener('paste', function (event) {
        let paste = (event.clipboardData || window.clipboardData).getData('text');
        if (isNaN(paste)) {
            event.preventDefault();
        }
    });
});
