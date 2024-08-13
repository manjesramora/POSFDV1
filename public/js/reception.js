$("#receptionForm").on("submit", function (e) {
    e.preventDefault(); // Evita el envío estándar del formulario

    // Mostrar modal de carga
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    loadingModal.show();

    // Prevenir el cierre del modal
    $('#loadingModal').modal({ backdrop: 'static', keyboard: false });

    // Enviar el formulario a través de AJAX
    const formData = new FormData(this);

    axios.post(this.action, formData)
        .then(response => {
            console.log(response); // Mostrar la respuesta completa en la consola

            if (response.data.success) {
                // Mostrar mensaje de éxito en el modal
                document.querySelector('.modal-body p').innerText = response.data.message;
                document.querySelector('.spinner-border').classList.add('d-none');
                document.getElementById('goToOrdersButton').classList.remove('d-none');
                document.getElementById('goToReceptionButton').classList.remove('d-none');
                document.getElementById('closeModalButton').classList.remove('d-none');
            } else {
                // Mostrar mensaje de error en el modal si success es false
                document.querySelector('.modal-body p').innerText = response.data.message || "Ocurrió un error inesperado.";
                document.querySelector('.spinner-border').classList.add('d-none');
                document.getElementById('closeModalButton').classList.remove('d-none');
            }
        })
        .catch(error => {
            // Manejar errores en la solicitud AJAX
            document.querySelector('.modal-body p').innerText = "Ocurrió un error durante la recepción.";
            document.querySelector('.spinner-border').classList.add('d-none');
            document.getElementById('closeModalButton').classList.remove('d-none');

            // Mostrar detalles del error en la consola
            console.error("Error durante la solicitud:", error.response ? error.response.data : error.message);
        });
});

// Evento para manejar la acción de regresar a órdenes
$("#goToOrdersButton").on("click", function () {
    window.location.href = "{{ route('orders') }}";
});

// Evento para manejar la acción de regresar a recepciones de compra
$("#goToReceptionButton").on("click", function () {
    window.location.href = "{{ route('receptions') }}";
});

// Evitar cerrar el modal accidentalmente y forzar la redirección a órdenes de compra
$("#closeModalButton").on("click", function () {
    // Bloquear el botón de cierre para impedir la salida sin regresar
    $(this).prop("disabled", true);
    alert("La orden ha sido procesada y no se puede editar. Será redirigido a las órdenes.");
    window.location.href = "{{ route('orders') }}";
});

// Autocompletado para el campo Número
$("#numero").on("input", function () {
    let query = $(this).val();

    if (query.length >= 3) {
        $.ajax({
            url: "/providers/autocomplete",
            type: "GET",
            data: {
                query: query,
                field: "CNCDIRID",
            },
            success: function (data) {
                let dropdown = $("#numeroList");
                dropdown.empty().show();

                data.forEach((item) => {
                    dropdown.append(
                        `<li class="list-group-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}">${item.CNCDIRID} - ${item.CNCDIRNOM}</li>`
                    );
                });
            },
        });
    } else {
        $("#numeroList").hide();
    }
});

$(document).on("click", "#numeroList li", function () {
    let id = $(this).data("id");
    let name = $(this).data("name");
    $("#numero").val(id);
    $("#fletero").val(name);
    $("#numeroList").hide();
});

$("#clearNumero").on("click", function () {
    $("#numero").val("");
    $("#fletero").val("");
    $("#numeroList").hide();
});

// Autocompletado para el campo Fletero
$("#fletero").on("input", function () {
    let query = $(this).val();

    if (query.length >= 3) {
        $.ajax({
            url: "/providers/autocomplete",
            type: "GET",
            data: {
                query: query,
                field: "CNCDIRNOM",
            },
            success: function (data) {
                let dropdown = $("#fleteroList");
                dropdown.empty().show();

                data.forEach((item) => {
                    dropdown.append(
                        `<li class="list-group-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}">${item.CNCDIRID} - ${item.CNCDIRNOM}</li>`
                    );
                });
            },
        });
    } else {
        $("#fleteroList").hide();
    }
});

$(document).on("click", "#fleteroList li", function () {
    let id = $(this).data("id");
    let name = $(this).data("name");
    $("#fletero").val(name);
    $("#numero").val(id);
    $("#fleteroList").hide();
});

$("#clearFletero").on("click", function () {
    $("#fletero").val("");
    $("#numero").val("");
    $("#fleteroList").hide();
});

// Bloquear y desbloquear el campo de flete
function toggleFleteInput() {
    const fleteSelect = document.getElementById('flete_select');
    const fleteInputDiv = document.getElementById('flete_input_div');
    if (fleteSelect.value == '1') {
        fleteInputDiv.style.display = 'block';
    } else {
        fleteInputDiv.style.display = 'none';
    }
}

// Lógica para limitar la cantidad recibida y el precio unitario
function limitInput(input, max) {
    let value = parseFloat(input.value);
    if (value > max) {
        input.value = max.toFixed(2);
    }
    if (value < 0) {
        input.value = 0;
    }
}

function limitCantidad(input) {
    const max = parseFloat(input.getAttribute("max"));
    let value = parseFloat(input.value);
    if (value > max) {
        input.value = max.toFixed(2);
    }
    if (value < 0) {
        input.value = 0;
    }
    calculateTotals(input);
}

function limitPrecio(input) {
    const max = parseFloat(input.getAttribute("max"));
    let value = parseFloat(input.value);
    if (value > max) {
        input.value = max.toFixed(2);
    }
    if (value < 0) {
        input.value = 0;
    }
    calculateTotals(input);
}

function calculateTotals(input) {
    const row = input.closest('tr');
    const cantidadRecibida = parseFloat(row.querySelector('.cantidad-recibida').value) || 0;
    const precioUnitario = parseFloat(row.querySelector('.precio-unitario').value) || 0;
    const iva = parseFloat(row.cells[8].innerText) || 0;

    const subtotal = cantidadRecibida * precioUnitario;
    const total = subtotal + (subtotal * (iva / 100));

    row.querySelector('.subtotal').innerText = subtotal.toFixed(2);
    row.querySelector('.total').innerText = total.toFixed(2);

    updateTotalCost();
}

function updateTotalCost() {
    let totalCost = 0;
    document.querySelectorAll('.total').forEach(totalCell => {
        totalCost += parseFloat(totalCell.innerText) || 0;
    });
    document.getElementById('totalCost').value = totalCost.toFixed(2);
}

// Evento de entrada para limitar la cantidad recibida
$(document).on("input", ".cantidad-recibida", function() {
    limitCantidad(this);
});

// Evento de entrada para limitar el precio unitario
$(document).on("input", ".precio-unitario", function() {
    limitPrecio(this);
});

// Validaciones en el evento 'change' para evitar incrementos invisibles
$(document).on("change", ".cantidad-recibida", function() {
    const max = parseFloat($(this).attr("max"));
    const value = parseFloat($(this).val());
    if (value > max) {
        $(this).val(max.toFixed(2));
    }
    if (value < 0) {
        $(this).val(0);
    }
    calculateTotals(this);
});

$(document).on("change", ".precio-unitario", function() {
    const max = parseFloat($(this).attr("max"));
    const value = parseFloat($(this).val());
    if (value > max) {
        $(this).val(max.toFixed(2));
    }
    if (value < 0) {
        $(this).val(0);
    }
    calculateTotals(this);
});
