$(document).ready(function () {
    // Función para limpiar el valor numérico, permitiendo un punto decimal
function cleanNumber(value) {
    // Permitir números y un solo punto decimal
    return value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');  // Mantiene el primer punto y borra cualquier segundo punto
}

let initialValue;  // Variable para almacenar el valor original al cargar la página

// Guardar el valor original que aparece en pantalla al cargar la página
$(document).ready(function () {
    $(".precio-unitario").each(function() {
        initialValue = parseFloat(cleanNumber($(this).val())) || 0;  // Almacenar el valor original al cargar
        $(this).data('initial-value', initialValue);  // Almacenar el valor en un atributo de datos
    });
});

// Limitar el precio al valor original solo si es mayor que el valor inicial al cargar la página
function limitPrecio(input) {
    let value = parseFloat(cleanNumber(input.value)) || 0;
    const originalValue = parseFloat($(input).data('initial-value')) || 0;  // Obtener el valor original cargado en pantalla

    // Solo si el nuevo valor es mayor que el original valor inicial, restaurar el original
    if (value > originalValue) {
        input.value = originalValue.toFixed(4);  // Restaurar al valor original de la carga inicial
    }

    // Recalcular los totales
    calculateTotals(input);
}

// Evento cuando el usuario deja de escribir (blur)
$(document).on("blur", ".precio-unitario", function () {
    limitPrecio(this);  // Validar y restaurar si es necesario
    formatPrecio(this);  // Formatear como moneda
});

// Mantener el formato de precio mientras el usuario escribe (para evitar borrar decimales válidos)
$(document).on("input", ".precio-unitario", function () {
    let value = cleanNumber(this.value);
    if (value !== '') {
        this.value = value;
    }
});

    function formatCurrency(value) {
        // Formatear valor como moneda con hasta cuatro decimales
        if (!isNaN(value) && value !== "") {
            return '$' + parseFloat(value).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
        }
        return '$0';
    }

    function formatPrecio(input) {
        let cleanValue = cleanNumber(input.value);
        input.value = cleanValue !== '' && !isNaN(cleanValue) ? formatCurrency(cleanValue) : ''; // Formatea si es válido
    }

    $(".precio-unitario, #flete").each(function () {
        formatPrecio(this); // Inicializa los campos de moneda
    });

    function limitCantidad(input) {
        const max = parseFloat(input.getAttribute("max")) || 0;
        let value = parseFloat(cleanNumber(input.value)) || 0;

        if (value > max) value = max; // Limita al valor máximo
        if (value < 0) value = 0; // Evita números negativos

        input.value = value.toString();
        calculateTotals(input);
    }


// Guardar el valor original antes de que el usuario lo cambie


    function calculateTotals(input) {
        const row = input.closest('tr');
        const cantidadRecibida = parseFloat(cleanNumber(row.querySelector('.cantidad-recibida').value)) || 0;
        const precioUnitario = parseFloat(cleanNumber(row.querySelector('.precio-unitario').value)) || 0;
        const iva = parseFloat(row.cells[10]?.innerText) || 0;

        const subtotal = cantidadRecibida * precioUnitario;
        const total = subtotal + (subtotal * (iva / 100));

        row.querySelector('.subtotal').innerText = formatCurrency(subtotal);
        row.querySelector('.total').innerText = formatCurrency(total);

        updateTotalCost();
    }

    function updateTotalCost() {
        let totalCost = 0;
        document.querySelectorAll('.total').forEach(totalCell => {
            totalCost += parseFloat(cleanNumber(totalCell.innerText)) || 0;
        });
        document.getElementById('totalCost').value = totalCost.toFixed(4).replace(/\.?0+$/, '');
    }

    $(document).on("input", ".cantidad-recibida", function () {
        let cleanValue = cleanNumber(this.value);
        const max = parseFloat(this.getAttribute('max')) || 0;
    
        if (!isNaN(cleanValue) && parseFloat(cleanValue) > max) {
            cleanValue = max.toFixed(4);
        }
    
        this.value = cleanValue !== '' ? cleanValue : '';
        calculateTotals(this);
    
        // Validar si todas las cantidades son 0 para mostrar/ocultar el mensaje de error
        if (!validateNonZeroQuantities()) {
            toggleErrorMessage(false); // Ocultar el mensaje de error si una cantidad es mayor a 0
        }
    });
    

    $(document).on("input", ".precio-unitario, #flete", function () {
        let cleanValue = cleanNumber(this.value);

        if (cleanValue === '' || cleanValue.endsWith('.') || (!isNaN(cleanValue) && cleanValue.split('.').length <= 2)) {
            this.value = cleanValue;
        }
    });

    $(document).on("blur", ".precio-unitario", function () {
        limitPrecio(this);
        formatPrecio(this);
    });

    $(document).on("blur", "#flete", function () {
        let cleanValue = cleanNumber(this.value);
        this.value = cleanValue !== '' && !isNaN(cleanValue) ? formatCurrency(cleanValue) : '';
    });

    $(document).ready(function () {
        // Limpiar el mensaje de error en el Monto Flete al escribir
        $('#flete').on('input', function () {
            const freightInput = $(this);
            freightInput[0].setCustomValidity(""); // Limpiar mensaje de error
            freightInput.removeClass("is-invalid"); // Remover clase de error
        });
    
        $("#receptionForm").on("submit", function (e) {
            e.preventDefault();
    
            // Limpiar los mensajes de error antes de la validación
            const freightInput = $('#flete');
            freightInput[0].setCustomValidity(""); // Limpiar mensaje de error
            freightInput.removeClass("is-invalid"); // Remover clase de error
    
            const carrierNumberInput = $('#numero');
            carrierNumberInput[0].setCustomValidity(""); // Limpiar mensaje de error
            carrierNumberInput.removeClass("is-invalid"); // Remover clase de error
    
            const carrierNameInput = $('#fletero');
            carrierNameInput[0].setCustomValidity(""); // Limpiar mensaje de error
            carrierNameInput.removeClass("is-invalid"); // Remover clase de error
    
            // Validar que al menos una cantidad recibida sea mayor que 0
            function validateNonZeroQuantities() {
                let allZero = true;
                $(".cantidad-recibida").each(function () {
                    const cantidad = parseFloat(cleanNumber($(this).val())) || 0;
                    if (cantidad > 0) {
                        allZero = false;
                        return false; // Sale del loop si encuentra una cantidad mayor a 0
                    }
                });
                return allZero;
            }
    
            // Validar que el monto de flete no sea 0 o esté vacío
            const freightValue = parseFloat(cleanNumber(freightInput.val())) || 0;
            const fleteSelect = document.getElementById('flete_select');
    
            if (fleteSelect.value == '1') {
                // Comprobar si el Monto Flete es 0 o no es un número
                if (freightValue <= 0 || isNaN(freightValue)) {
                    freightInput[0].setCustomValidity("El Monto Flete debe ser mayor que 0."); // Establece mensaje de error
                    freightInput.addClass("is-invalid"); // Añadir clase de error
                    freightInput.focus(); // Coloca el foco en el campo Monto Flete
                    return; // Detener el envío del formulario
                }
    
                // Validar que el campo # numero (Fletero) no esté vacío
                const carrierNumber = carrierNumberInput.val().trim();
                if (carrierNumber === '') {
                    carrierNumberInput[0].setCustomValidity("El campo # Fletero es obligatorio."); // Establece mensaje de error
                    carrierNumberInput.addClass("is-invalid"); // Añadir clase de error
                    carrierNumberInput.focus(); // Coloca el foco en el campo # Fletero
                    return; // Detener el envío del formulario
                }
    
                // Validar que el campo Nombre Fletero no esté vacío
                const carrierName = carrierNameInput.val().trim();
                if (carrierName === '') {
                    carrierNameInput[0].setCustomValidity("El campo Nombre Fletero es obligatorio."); // Establece mensaje de error
                    carrierNameInput.addClass("is-invalid"); // Añadir clase de error
                    carrierNameInput.focus(); // Coloca el foco en el campo Nombre Fletero
                    return; // Detener el envío del formulario
                }
            }
    
            // Validar que al menos una cantidad recibida sea mayor que 0
            if (validateNonZeroQuantities()) {
                alert("No es posible realizar la recepción si todas las Cantidades Recibidas son 0."); // Mensaje de error
                return; // Detener el envío del formulario
            }
    
            // Si todas las validaciones pasan, proceder con el envío
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();
    
            // Obtener el formulario para enviarlo
            const formData = new FormData(this);
            
            // Código para enviar el formulario utilizando axios
            axios.post(this.action, formData)
                .then(response => {
                    if (response.data.success) {
                        // Manejo del éxito de la recepción
                        document.querySelector('.modal-body p').innerHTML = `
                            ${response.data.message}
                            <br><br>
                            <button id="printReportButton" class="btn btn-success">Imprimir Reporte</button>
                            <button id="goToOrdersButton" class="btn btn-primary">Regresar a Órdenes</button>
                        `;
                        document.querySelector('.spinner-border').classList.add('d-none');
                        document.getElementById('printReportButton').classList.remove('d-none');
                        document.getElementById('closeModalButton').classList.add('d-none');
    
                        // Botón para imprimir el reporte
                        document.getElementById('printReportButton').addEventListener('click', function () {
                            const ACMROINDOC = response.data.ACMROINDOC;
                            const ACMROIDOC = response.data.ACMROIDOC;
    
                            if (ACMROINDOC && ACMROIDOC) {
                                axios.get(`/print-report/${ACMROINDOC}`, {
                                    responseType: 'blob'
                                })
                                .then(pdfResponse => {
                                    const fileURL = URL.createObjectURL(new Blob([pdfResponse.data], { type: 'application/pdf' }));
                                    window.open(fileURL, '_blank');
                                })
                                .catch(error => {
                                    console.error("Error loading the PDF:", error);
                                    alert("Error al cargar el PDF. Inténtalo nuevamente.");
                                });
                            } else {
                                alert("Error: ACMROINDOC o ACMROIDOC faltantes.");
                            }
                        });
                    } else {
                        // Manejo de errores en la recepción
                        document.querySelector('.modal-body p').innerHTML = `
                            ${response.data.message || "Ocurrió un error inesperado."}
                            <br><br>
                            <button id="goToOrdersButton" class="btn btn-primary">Regresar a Órdenes</button>
                        `;
                        document.querySelector('.spinner-border').classList.add('d-none');
                        document.getElementById('goToOrdersButton').classList.remove('d-none');
                        document.getElementById('closeModalButton').classList.add('d-none');
                    }
    
                    document.getElementById('goToOrdersButton').addEventListener('click', function () {
                        window.location.href = '/orders';
                    });
                })
                .catch(error => {
                    // Manejo de errores durante el envío del formulario
                    document.querySelector('.modal-body p').innerHTML = `
                        Ocurrió un error durante la recepción.
                        <br><br>
                        <button id="goToOrdersButton" class="btn btn-primary">Regresar a Órdenes</button>
                    `;
                    document.querySelector('.spinner-border').classList.add('d-none');
                    document.getElementById('goToOrdersButton').classList.remove('d-none');
                    document.getElementById('closeModalButton').classList.add('d-none');
    
                    document.getElementById('goToOrdersButton').addEventListener('click', function () {
                        window.location.href = '/orders';
                    });
                });
        });
    });
    

    function setupAutocomplete(inputId, listId, field) {
        $(`#${inputId}`).on("input", function () {
            let query = $(this).val();
    
            if (query.length >= 3) {
                $.ajax({
                    url: "/providers/autocomplete",
                    type: "GET",
                    data: { query, field },
                    success: function (data) {
                        let dropdown = $(`#${listId}`);
                        dropdown.empty().show();
    
                        // Filtrar resultados: excluir fleteros cuyo CNCDIRID empiece con '3'
                        data = data.filter(item => !item.CNCDIRID.startsWith('3'));
    
                        // Agregar los elementos filtrados al dropdown
                        data.forEach((item) => {
                            dropdown.append(
                                `<li class="list-group-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}">${item.CNCDIRID} - ${item.CNCDIRNOM}</li>`
                            );
                        });
    
                        if (data.length > 0) {
                            dropdown.children().first().addClass('active');
                        }
                    },
                });
            } else {
                $(`#${listId}`).hide();
            }
        });
    
        $(document).on("keydown", function (e) {
            const dropdown = $(`#${listId}`);
            const items = dropdown.find('li');
            let activeItem = dropdown.find('.active');
            let index = items.index(activeItem);
        
            if (e.key === "ArrowDown") {
                e.preventDefault();
                index = (index + 1) % items.length;
                items.removeClass('active').eq(index).addClass('active'); // Cambia la clase active
                dropdown.scrollTop(dropdown.scrollTop() + items.eq(index).position().top);
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                index = (index - 1 + items.length) % items.length;
                items.removeClass('active').eq(index).addClass('active'); // Cambia la clase active
                dropdown.scrollTop(dropdown.scrollTop() + items.eq(index).position().top);
            } else if (e.key === "Enter" && activeItem.length) {
                e.preventDefault();
                const id = activeItem.data('id');
                const name = activeItem.data('name');
                $(`#${inputId}`).val(id);
                $('#fletero').val(name);
                $('#numero').val(id);
                dropdown.hide();
            }
        });
        
    
        $(document).on("click", `#${listId} li`, function () {
            let id = $(this).data("id");
            let name = $(this).data("name");
            $(`#${inputId}`).val(id);
            $('#fletero').val(name);
            $('#numero').val(id);
            $(`#${listId}`).hide();
        });
    
        $(`#clear${inputId.charAt(0).toUpperCase() + inputId.slice(1)}`).on("click", function () {
            $(`#${inputId}`).val("");
            $('#fletero').val("");
            $('#numero').val("");
            $(`#${listId}`).hide();
        });
    }
    

    setupAutocomplete("numero", "numeroList", "CNCDIRID");
    setupAutocomplete("fletero", "fleteroList", "CNCDIRNOM");

    function toggleFleteInput() {
        const fleteSelect = document.getElementById('flete_select');
        const fleteInputDiv = document.getElementById('flete_input_div');
        const fleteroFields = document.getElementById('fletero_fields');
        const fleteroFieldsName = document.getElementById('fletero_fields_name');
    
        if (fleteSelect.value == '1') {
            fleteInputDiv.style.display = 'block';
            fleteroFields.style.display = 'block';
            fleteroFieldsName.style.display = 'block';
    
            // Hacer los campos obligatorios
            document.getElementById('numero').setAttribute('required', 'required');
            document.getElementById('fletero').setAttribute('required', 'required');
        } else {
            fleteInputDiv.style.display = 'none';
            fleteroFields.style.display = 'none';
            fleteroFieldsName.style.display = 'none';
    
            // Remover la obligación de los campos
            document.getElementById('numero').removeAttribute('required');
            document.getElementById('fletero').removeAttribute('required');
        }
    }
    
    // Añadir el evento de cambio para el campo de selección de flete
    $('#flete_select').on('change', toggleFleteInput);
    
});

document.addEventListener('DOMContentLoaded', function () {
    // Seleccionar todos los inputs de cantidad recibida
    const cantidadRecibidaInputs = document.querySelectorAll('.cantidad-recibida');

    // Iterar sobre los inputs para agregar un evento "input"
    cantidadRecibidaInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            const maxCantidad = parseFloat(this.getAttribute('max')); // Obtener el valor máximo permitido
            const currentCantidad = parseFloat(this.value); // Obtener el valor ingresado

            if (currentCantidad > maxCantidad) {
                // Mostrar un mensaje de error visual al usuario
                const mensajeError = document.createElement('div');
                mensajeError.classList.add('alert', 'alert-danger', 'mt-2');
                mensajeError.textContent = "La cantidad recibida no puede ser mayor a la cantidad solicitada.";
                
                // Insertar el mensaje de error después del input
                if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('alert')) {
                    this.parentNode.insertBefore(mensajeError, this.nextElementSibling);
                }

                // Restablecer el valor al máximo permitido
                this.value = maxCantidad;
            } else {
                // Si la cantidad es válida, eliminar el mensaje de error
                if (this.nextElementSibling && this.nextElementSibling.classList.contains('alert')) {
                    this.nextElementSibling.remove();
                }
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const cantidadRecibidaInputs = document.querySelectorAll('.cantidad-recibida');

    cantidadRecibidaInputs.forEach(function(input) {
        input.addEventListener('input', function () {
            let value = this.value;

            // Permitir solo números y un punto decimal
            if (!/^\d*\.?\d*$/.test(value)) {
                // Si el valor no es un número válido, eliminar los caracteres no válidos
                this.value = value.replace(/[^0-9.]/g, '');
            }

            // Limitar la cantidad a la cantidad solicitada
            const maxCantidad = parseFloat(this.getAttribute('max'));
            if (parseFloat(this.value) > maxCantidad) {
                alert("La cantidad recibida no puede ser mayor a la cantidad solicitada.");
                this.value = maxCantidad;
            }
        });

        // Asegurar que no se pueda introducir más de un punto decimal
        input.addEventListener('keydown', function(event) {
            if (event.key === '.' && this.value.includes('.')) {
                event.preventDefault(); // Evitar que se introduzca un segundo punto decimal
            }
        });
    });
});

$(document).ready(function () {
    // Navegación con las flechas del teclado
    $(document).on("keydown", ".form-control", function (e) {
        const keyCode = e.which || e.keyCode;

        const currentInput = $(this); // El input actual en el que estamos
        const currentCell = currentInput.closest('td'); // La celda actual
        const currentRow = currentCell.closest('tr'); // La fila actual
        const currentIndex = currentCell.index(); // Índice de la celda actual

        if (keyCode === 37) { // Flecha izquierda
            currentCell.prev().find('input').focus();
            e.preventDefault();
        } else if (keyCode === 39) { // Flecha derecha
            currentCell.next().find('input').focus();
            e.preventDefault();
        } else if (keyCode === 38) { // Flecha arriba
            const prevRow = currentRow.prev();
            if (prevRow.length > 0) {
                prevRow.find('td').eq(currentIndex).find('input').focus();
                e.preventDefault();
            }
        } else if (keyCode === 40) { // Flecha abajo
            const nextRow = currentRow.next();
            if (nextRow.length > 0) {
                nextRow.find('td').eq(currentIndex).find('input').focus();
                e.preventDefault();
            }
        }
    });
});

$(document).ready(function () {
    $('input[type="text"], input[type="number"]').attr('autocomplete', 'off');
});

// Mostrar el botón cuando el usuario desplaza hacia abajo 200px desde la parte superior
window.onscroll = function() {
    scrollFunction();
};

function scrollFunction() {
    const scrollToTopBtn = document.getElementById("scrollToTopBtn");
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        scrollToTopBtn.style.display = "block";
    } else {
        scrollToTopBtn.style.display = "none";
    }
}

// Función para volver a la parte superior cuando se hace clic en el botón
function topFunction() {
    document.body.scrollTop = 0; // Para Safari
    document.documentElement.scrollTop = 0; // Para Chrome, Firefox, IE y Opera
}

$(document).ready(function() {
    // Función para recalcular el subtotal y el total de una fila
    function recalcularFila(row) {
        // Obtener la cantidad recibida y el precio unitario de la fila
        let cantidadRecibida = parseFloat(row.find('input.cantidad-recibida').val()) || 0;
        let precioUnitario = parseFloat(row.find('input.precio-unitario').val().replace(/[^0-9.-]+/g, "")) || 0;
    
        // Obtener el valor del IVA (columna 11)
        let iva = parseFloat(row.find('td:nth-child(11)').text()) || 0;  
    
        // Calcular el subtotal (Cantidad Recibida * Precio Unitario)
        let subtotal = cantidadRecibida * precioUnitario;
        row.find('td.subtotal').text(`$${subtotal.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 })}`);
    
        // Calcular el total incluyendo el IVA
        let total = subtotal + (subtotal * (iva / 100));  // Aplicar el IVA al subtotal
        row.find('td.total').text(`$${total.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 })}`);
    }
    

    // Función para recalcular los totales de todas las filas
    function recalcularTotales() {
        let totalSubtotal = 0;
        let totalTotal = 0;
    
        // Iterar sobre cada fila de la tabla para sumar los valores de las columnas Subtotal y Total
        $('#receptionTableBody tr').each(function() {
            // Obtener el subtotal y el total de cada fila
            let subtotal = parseFloat($(this).find('td.subtotal').text().replace(/[^0-9.-]+/g, "")) || 0;
            let total = parseFloat($(this).find('td.total').text().replace(/[^0-9.-]+/g, "")) || 0;
    
            // Sumar al total general
            totalSubtotal += subtotal;
            totalTotal += total;
        });
    
        // Colocar los valores calculados en las celdas correspondientes de la fila de totales
        $('#total-subtotal').text(`$${totalSubtotal.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 })}`);
        $('#total-total').text(`$${totalTotal.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 })}`);
    }
    
    

    // Ejecutar la función cada vez que se actualiza una cantidad recibida o el precio unitario
   // Recalcular la fila al cambiar la cantidad recibida o el precio unitario
$('.cantidad-recibida, .precio-unitario').on('input', function() {
    let row = $(this).closest('tr'); // Encontrar la fila correspondiente
    recalcularFila(row); // Recalcular el subtotal y el total de esa fila
    recalcularTotales(); // Recalcular los totales generales
});

    // Ejecutar al cargar la página para calcular los valores iniciales
    recalcularTotales();
});
