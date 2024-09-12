$(document).ready(function () {
    function cleanNumber(value) {
        return value.replace(/[^0-9.]/g, '').trim(); // Solo permite números y decimales
    }

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

    function limitPrecio(input) {
        let value = parseFloat(cleanNumber(input.value)) || 0;
        const originalValue = parseFloat(input.getAttribute('data-original-value')) || 0;

        if (value > originalValue || isNaN(value) || value < 0) {
            value = originalValue;
        }

        input.value = value;
        calculateTotals(input);
    }

    function calculateTotals(input) {
        const row = input.closest('tr');
        const cantidadRecibida = parseFloat(cleanNumber(row.querySelector('.cantidad-recibida').value)) || 0;
        const precioUnitario = parseFloat(cleanNumber(row.querySelector('.precio-unitario').value)) || 0;
        const iva = parseFloat(row.cells[8]?.innerText) || 0;

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
    
        this.value = cleanValue !== '' ? cleanValue : '0';
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

    $("#receptionForm").on("submit", function (e) {
        e.preventDefault();
    
        // Función para validar que al menos una cantidad recibida sea mayor que 0
        function validateNonZeroQuantities() {
            let allZero = true;
            // Recorre todas las cantidades recibidas
            $(".cantidad-recibida").each(function () {
                const cantidad = parseFloat(cleanNumber($(this).val())) || 0;
                if (cantidad > 0) {
                    allZero = false;
                    return false; // Sale del loop si encuentra una cantidad mayor a 0
                }
            });
            return allZero;
        }
    
        // Mostrar/ocultar el mensaje de error
        function toggleErrorMessage(show) {
            const errorDiv = $("#reception-error");
            if (show) {
                errorDiv.removeClass("d-none");
            } else {
                errorDiv.addClass("d-none");
            }
        }
    
        // Verificar si todas las cantidades recibidas son 0
        if (validateNonZeroQuantities()) {
            toggleErrorMessage(true); // Mostrar el mensaje de error
            return; // Detener el envío del formulario
        }
    
        toggleErrorMessage(false); // Ocultar el mensaje si la validación es correcta
    
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
    
        const formData = new FormData(this);
    
        axios.post(this.action, formData)
            .then(response => {
                if (response.data.success) {
                    document.querySelector('.modal-body p').innerHTML = `
                        ${response.data.message}
                        <br><br>
                        <button id="printReportButton" class="btn btn-success">Imprimir Reporte</button>
                        <button id="goToOrdersButton" class="btn btn-primary">Regresar a Órdenes</button>
                    `;
                    document.querySelector('.spinner-border').classList.add('d-none');
                    document.getElementById('printReportButton').classList.remove('d-none');
                    document.getElementById('closeModalButton').classList.add('d-none');
    
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

        $(`#${inputId}`).on("keydown", function (e) {
            const dropdown = $(`#${listId}`);
            const items = dropdown.find('li');
            let activeItem = dropdown.find('.active');
            let index = items.index(activeItem);

            if (e.key === "ArrowDown") {
                e.preventDefault();
                index = (index + 1) % items.length;
                items.removeClass('active').eq(index).addClass('active');
                dropdown.scrollTop(dropdown.scrollTop() + items.eq(index).position().top);
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                index = (index - 1 + items.length) % items.length;
                items.removeClass('active').eq(index).addClass('active');
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
        } else {
            fleteInputDiv.style.display = 'none';
            fleteroFields.style.display = 'none';
            fleteroFieldsName.style.display = 'none';
        }
    }

    $('#flete_select').on('change', toggleFleteInput);
});
