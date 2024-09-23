$(document).ready(function () {
    let currentFocus = -1; // Mantiene el seguimiento del ítem seleccionado

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
                    currentFocus = -1; // Reiniciar el índice de enfoque

                    data.forEach((item, index) => {
                        if (item.CNCDIRID.startsWith("3")) {
                            dropdown.append(
                                `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}" tabindex="${index}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                            );
                        }
                    });

                    // Marcar el primer elemento como activo por defecto
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
                    currentFocus = -1; // Reiniciar el índice de enfoque

                    data.forEach((item, index) => {
                        if (item.CNCDIRID.startsWith("4")) {
                            dropdown.append(
                                `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}" tabindex="${index}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                            );
                        }
                    });

                    // Marcar el primer elemento como activo por defecto
                    dropdown.find(".dropdown-item").first().addClass("active");
                },
            });
        } else {
            $("#transporterDropdown").hide();
        }
    });

    // Manejar la navegación con las teclas arriba/abajo y Enter para el dropdown de proveedores
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
                items[currentFocus].click();
            } else {
                $("#filterForm").submit(); // Enviar formulario si no hay un dropdown activo
            }
        }
    });

    // Manejar la navegación con las teclas arriba/abajo y Enter para el dropdown de transportistas
    $("#CNCDIRNOM_TRANSP").on("keydown", function (e) {
        let dropdown = $("#transporterDropdown");
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
                items[currentFocus].click();
            } else {
                $("#filterForm").submit(); // Enviar formulario si no hay un dropdown activo
            }
        }
    });

    // Función para añadir clase "active" al ítem seleccionado
    function setActive(items) {
        removeActive(items);
        if (currentFocus >= 0 && currentFocus < items.length) {
            $(items[currentFocus]).addClass("active");
            items[currentFocus].scrollIntoView({ block: "nearest" });
        }
    }

    // Función para eliminar la clase "active" de todos los ítems
    function removeActive(items) {
        items.removeClass("active");
    }

    // Selección de un proveedor o transportista del dropdown mediante clic
    $(document).on("click", ".dropdown-item", function () {
        let item = $(this);
        if (item.closest("#nameDropdown").length) {
            $("#CNCDIRNOM").val(item.data("name"));
        } else if (item.closest("#transporterDropdown").length) {
            $("#CNCDIRNOM_TRANSP").val(item.data("name"));
        }
    
        // Solo envía el formulario si el campo está lleno
        if ($("#CNCDIRNOM").val() || $("#CNCDIRNOM_TRANSP").val()) {
            $("#filterForm").submit();
        }
    
        $("#nameDropdown, #transporterDropdown").hide();
    });
    
    // Clear all filter input fields and reload the page
    window.limpiarCampos = function () {
        window.location.href = "/freights"; // Redirigir a la URL original sin parámetros
    };

    // Validar fechas y aplicar lógica de máximo 2 años y hasta la fecha actual
    $("#filterForm").on("submit", function (event) {
        let startDate = $("#start_date").val();
        let endDate = $("#end_date").val();

        // Obtener la fecha actual
        let today = new Date();
        let dd = String(today.getDate()).padStart(2, '0');
        let mm = String(today.getMonth() + 1).padStart(2, '0'); // Enero es 0
        let yyyy = today.getFullYear();
        let currentDate = `${yyyy}-${mm}-${dd}`;

        // Si no hay fecha de fin, usar la fecha actual
        if (startDate && !endDate) {
            $("#end_date").val(currentDate);
        }

        // Verificar que el rango no exceda los 2 años
        if (startDate) {
            let start = new Date(startDate);
            let twoYearsAgo = new Date(start);
            twoYearsAgo.setFullYear(start.getFullYear() + 2);

            if (new Date(endDate || currentDate) > twoYearsAgo) {
                alert("El rango de fechas no puede ser mayor a 2 años.");
                event.preventDefault();
            }
        }
    });
});

$(document).ready(function () {
    // Limitar la selección de fechas a 2 años atrás desde la fecha actual
    let today = new Date();
    let maxStartDate = new Date();
    maxStartDate.setFullYear(today.getFullYear() - 2);

    // Formato de las fechas
    let formattedToday = today.toISOString().split('T')[0];
    let formattedMaxStartDate = maxStartDate.toISOString().split('T')[0];

    // Establecer el atributo "max" y "min" en los inputs de fecha
    $("#start_date").attr("min", formattedMaxStartDate);
    $("#start_date").attr("max", formattedToday);
    $("#end_date").attr("min", formattedMaxStartDate);
    $("#end_date").attr("max", formattedToday);

    // Validar fechas y aplicar lógica de máximo 2 años
    $("#filterForm").on("submit", function (event) {
        let startDate = $("#start_date").val();
        let endDate = $("#end_date").val();

        if (!endDate && startDate) {
            $("#end_date").val(formattedToday);
        }

        if (startDate) {
            let start = new Date(startDate);
            let twoYearsFromStart = new Date(start);
            twoYearsFromStart.setFullYear(start.getFullYear() + 2);

            if (new Date(endDate || formattedToday) > twoYearsFromStart) {
                alert("El rango de fechas no puede ser mayor a 2 años.");
                event.preventDefault();
            }
        }
    });
});
