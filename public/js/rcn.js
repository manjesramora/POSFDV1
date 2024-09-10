$(document).ready(function () {
    let currentFocus = -1;

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
                    currentFocus = -1; // Reiniciar el índice de enfoque cuando se muestran nuevos resultados

                    data.forEach((item, index) => {
                        dropdown.append(
                            `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}" tabindex="${index}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                        );
                    });

                    // Marcar el primer elemento como activo por defecto
                    dropdown.find(".dropdown-item").first().addClass("active");
                    currentFocus = 0; // Poner el enfoque en el primer ítem
                },
            });
        } else {
            $("#nameDropdown").hide();
        }
    });

    // Selección de un proveedor del dropdown (clic)
    $(document).on("click", ".dropdown-item", function () {
        let item = $(this);
        $("#CNCDIRNOM").val(item.data("name"));
        $("#nameDropdown").hide();
        // Envía el formulario automáticamente cuando selecciona un proveedor del dropdown
        $("#combinedForm").submit();
    });

    // Manejar la navegación con las teclas arriba/abajo y Enter
    $("#CNCDIRNOM").on("keydown", function (e) {
        let dropdown = $("#nameDropdown");
        let items = dropdown.find(".dropdown-item");

        if (e.keyCode === 40) { // Flecha abajo
            currentFocus++;
            if (currentFocus >= items.length) currentFocus = 0;
            setActive(items);
        } else if (e.keyCode === 38) { // Flecha arriba
            currentFocus--;
            if (currentFocus < 0) currentFocus = items.length - 1;
            setActive(items);
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if (currentFocus > -1 && items.length > 0) {
                items[currentFocus].click(); // Selecciona el ítem activo
            } else {
                // Si no hay elementos seleccionados en el dropdown, filtrar con lo que esté escrito
                $("#nameDropdown").hide(); // Oculta el dropdown
                $("#combinedForm").submit(); // Enviar el formulario
            }
        }
    });

    // Añadir clase activa al ítem seleccionado
    function setActive(items) {
        removeActive(items);
        if (currentFocus >= 0 && currentFocus < items.length) {
            $(items[currentFocus]).addClass("active");
            items[currentFocus].scrollIntoView({ block: "nearest" });
        }
    }

    // Eliminar clase activa de todos los ítems
    function removeActive(items) {
        items.removeClass("active");
    }

    // Limpiar todos los campos de filtro y recargar la página
    window.limpiarCampos = function () {
        window.location.href = "/rcn"; // Redirigir a la URL original sin parámetros
    };

    // Validar las fechas y asignar fecha actual si el campo "Hasta" está vacío
    $("#combinedForm").on("submit", function() {
        var startDate = $("#start_date").val();
        var endDate = $("#end_date").val();

        // Obtener la fecha actual en formato YYYY-MM-DD
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); // Enero es 0
        var yyyy = today.getFullYear();
        var currentDate = yyyy + '-' + mm + '-' + dd;

        // Si hay una fecha de inicio pero no una fecha de fin, asigna la fecha actual como fin
        if (startDate && !endDate) {
            $("#end_date").val(currentDate);
        }
    });
});

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
});
