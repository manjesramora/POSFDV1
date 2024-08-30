$(document).ready(function () {
    function cleanNumber(value) {
        return value.replace(/,/g, '').replace('$', '');
    }

    function formatCurrency(value) {
        return '$' + parseFloat(value).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatPrecio(input) {
        const value = cleanNumber(input.value);
        if (!isNaN(value) && value.trim() !== "") {
            input.value = formatCurrency(value);
        }
    }

    function limitCantidad(input) {
        const max = parseFloat(cleanNumber(input.getAttribute("max")));
        let value = parseFloat(cleanNumber(input.value));
        if (value > max) {
            input.value = max.toFixed(4).replace(/\.?0+$/, '');
        }
        if (value < 0) {
            input.value = 0;
        }
        calculateTotals(input);
    }

    function limitPrecio(input) {
        let value = parseFloat(cleanNumber(input.value));
        const originalValue = parseFloat(input.getAttribute('data-original-value'));

        if (value > originalValue) {
            input.value = originalValue.toFixed(4).replace(/\.?0+$/, '');
        } else if (value < 0) {
            input.value = 0;
        } else {
            input.value = value.toFixed(4).replace(/\.?0+$/, '');
        }

        input.value = formatCurrency(input.value);
        calculateTotals(input);
    }

    function calculateTotals(input) {
        const row = input.closest('tr');
        const cantidadRecibida = parseFloat(cleanNumber(row.querySelector('.cantidad-recibida').value)) || 0;
        const precioUnitario = parseFloat(cleanNumber(row.querySelector('.precio-unitario').value)) || 0;
        const iva = parseFloat(cleanNumber(row.cells[8].innerText)) || 0;

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
        limitCantidad(this);
    });

    $(document).on("input", ".precio-unitario", function () {
        formatPrecio(this);
    });

    $(document).on("change", ".cantidad-recibida", function () {
        limitCantidad(this);
    });

    $(document).on("change", ".precio-unitario", function () {
        limitPrecio(this);
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

                    // Modifica el comportamiento para abrir o descargar el PDF
                    document.getElementById('printReportButton').addEventListener('click', function () {
                        axios.get(`/print-report?ACMROINDOC=${response.data.ACMROINDOC}&ACMROIDOC=${response.data.ACMROIDOC}`, {
                            responseType: 'blob' // Importante para el PDF
                        }).then(pdfResponse => {
                            const fileURL = URL.createObjectURL(new Blob([pdfResponse.data], { type: 'application/pdf' }));
                            
                            // Abre el PDF en una nueva pestaña
                            window.open(fileURL, '_blank');

                            // Alternativamente, forzar descarga del PDF
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
        fleteInputDiv.style.display = fleteSelect.value == '1' ? 'block' : 'none';
    }

    $('#flete_select').on('change', toggleFleteInput);
});
