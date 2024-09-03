$(document).ready(function () {
    function cleanNumber(value) {
        // Permitir solo números y puntos decimales
        return value.replace(/[^0-9.]/g, '').trim();
    }

    function formatCurrency(value) {
        // Formatear el valor como moneda con hasta cuatro decimales
        if (!isNaN(value) && value !== "") {
            return '$' + parseFloat(value).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
        }
        return '$0';
    }

    function formatPrecio(input) {
        let cleanValue = cleanNumber(input.value);

        if (cleanValue === '') {
            input.value = '';
        } else if (!isNaN(cleanValue)) {
            input.value = formatCurrency(cleanValue);
        }
    }

    // Inicializa los campos con formato de moneda
    $(".precio-unitario, #flete").each(function () {
        formatPrecio(this);
    });

    function limitCantidad(input) {
        const max = parseFloat(input.getAttribute("max"));
        let value = cleanNumber(input.value);

        // Permitir ingreso de decimales con ceros al final
        let numericValue = parseFloat(value);
        
        // Validar valor máximo y mínimo
        if (!isNaN(numericValue) && numericValue > max) {
            numericValue = max;
        } else if (numericValue < 0 || isNaN(numericValue)) {
            numericValue = 0;
        }

        input.value = numericValue.toString();

        calculateTotals(input);
    }

    function limitPrecio(input) {
        let value = parseFloat(cleanNumber(input.value));
        const originalValue = parseFloat(input.getAttribute('data-original-value'));

        // Limitar el precio al valor original si es mayor o NaN o negativo
        if (isNaN(value) || value < 0 || value > originalValue) {
            value = originalValue;
        }

        input.value = value;

        calculateTotals(input);
    }

    function calculateTotals(input) {
        const row = input.closest('tr');
        const cantidadRecibida = parseFloat(cleanNumber(row.querySelector('.cantidad-recibida').value)) || 0;
        const precioUnitario = parseFloat(cleanNumber(row.querySelector('.precio-unitario').value)) || 0;
        const iva = parseFloat(row.cells[8].innerText) || 0;

        // Calcular subtotal y total con cantidad recibida incluyendo 0
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
        const max = parseFloat(this.getAttribute('max'));

        // Permitir la entrada de decimales con ceros al final
        const parts = cleanValue.split('.');
        if (parts.length > 2) {
            cleanValue = parts[0] + '.' + parts[1]; // Eliminar puntos adicionales
        }

        // Validar el límite máximo solo si el valor no es NaN y es mayor al máximo
        if (!isNaN(cleanValue) && parseFloat(cleanValue) > max) {
            cleanValue = max.toFixed(4); // Limitar al valor máximo permitido
        }

        // Asegurar que el valor 0 sea mostrado correctamente, incluyendo decimales como 10.0, 10.00, etc.
        this.value = cleanValue !== '' ? cleanValue : '0';

        calculateTotals(this);
    });

    $(document).on("input", ".precio-unitario, #flete", function () {
        let inputVal = this.value;
        let cleanValue = cleanNumber(inputVal);

        // Permitir la entrada de decimales y punto al final
        if (cleanValue === '' || cleanValue.endsWith('.') || (!isNaN(cleanValue) && cleanValue.split('.').length <= 2)) {
            this.value = cleanValue; // Permitir decimales y el punto al final
        }
    });

    $(document).on("blur", ".precio-unitario", function () {
        limitPrecio(this);
        formatPrecio(this); // Formatear como moneda al perder el foco si el valor es válido
    });

    $(document).on("blur", "#flete", function () {
        let cleanValue = cleanNumber(this.value);
        if (cleanValue !== '' && !isNaN(cleanValue)) {
            this.value = formatCurrency(cleanValue);
        } else {
            this.value = ''; // Si no es válido, mantener vacío
        }
    });

    $("#receptionForm").on("submit", function (e) {
        e.preventDefault();

        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();

        const formData = new FormData(this);

        axios.post(this.action, formData)
            .then(response => {
                console.log(response);

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
                        axios.get(`/print-report?ACMROINDOC=${response.data.ACMROINDOC}&ACMROIDOC=${response.data.ACMROIDOC}`, {
                            responseType: 'blob'
                        }).then(pdfResponse => {
                            const fileURL = URL.createObjectURL(new Blob([pdfResponse.data], { type: 'application/pdf' }));
                            window.open(fileURL, '_blank');
                            const downloadLink = document.createElement('a');
                            downloadLink.href = fileURL;
                            downloadLink.download = `order_report_${response.data.ACMROINDOC}.pdf`;
                            downloadLink.click();
                        }).catch(error => {
                            console.error("Error loading the PDF:", error);
                        });
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
                    window.location.href = "{{ route('orders') }}";
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
                    window.location.href = "{{ route('orders') }}";
                });

                console.error("Error durante la solicitud:", error.response ? error.response.data : error.message);
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
