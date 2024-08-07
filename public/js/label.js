// Función para mostrar la animación de "Cargando"
function showLoading() {
    document.getElementById("loadingOverlay").style.display = "flex";
}

// Función para ocultar la animación de "Cargando"
function hideLoading() {
    document.getElementById("loadingOverlay").style.display = "none";
}

// Modificar la función buscarFiltros para incluir la animación
function buscarFiltros() {
    showLoading(); // Mostrar la animación de "Cargando"

    // Obtener los valores de los filtros desde los elementos del DOM
    var productId = document.getElementById("productId").value;
    var sku = document.getElementById("sku").value;
    var name = document.getElementById("name").value;
    var linea = document.getElementById("linea").value;
    var sublinea = document.getElementById("sublinea").value;
    var departamento = document.getElementById("departamento").value;

    // Incluir valores por defecto para Línea y Sublinea
    if (linea) {
        linea = "LN" + linea;
    }
    if (sublinea) {
        sublinea = "SB" + sublinea;
    }

    var url = new URL(window.location.href);

    // Eliminar los parámetros anteriores de la URL
    url.searchParams.delete("productId");
    url.searchParams.delete("sku");
    url.searchParams.delete("name");
    url.searchParams.delete("linea");
    url.searchParams.delete("sublinea");
    url.searchParams.delete("departamento");
    url.searchParams.delete("page"); // Reiniciar el paginado a la página 1

    // Establecer los nuevos parámetros de búsqueda
    if (productId) url.searchParams.set("productId", productId);
    if (sku) url.searchParams.set("sku", sku);
    if (name) url.searchParams.set("name", name);
    if (linea) url.searchParams.set("linea", linea);
    if (sublinea) url.searchParams.set("sublinea", sublinea);
    if (departamento) url.searchParams.set("departamento", departamento);

    // Actualizar la URL del navegador
    window.history.pushState({}, "", url);

    // Realizar la solicitud fetch para actualizar la tabla y la paginación
    fetch(url)
        .then((response) => response.text())
        .then((html) => {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, "text/html");
            var newContent = doc.getElementById("proveedorTable").innerHTML;
            var newPagination = doc.getElementById("pagination-links").innerHTML;
            document.getElementById("proveedorTable").innerHTML = newContent;
            document.getElementById("pagination-links").innerHTML = newPagination;

            // Reattach event listeners for pagination links
            reattachPaginationEventListeners();
        })
        .catch((error) => console.error('Error en la solicitud fetch:', error))
        .finally(() => hideLoading()); // Ocultar la animación de "Cargando" al finalizar
}

// Función para manejar el evento de tecla presionada en los campos de entrada
function handleKeyPress(event) {
    if (event.keyCode === 13) { // Código 13 es Enter
        event.preventDefault(); // Prevenir el comportamiento por defecto (enviar formulario)
        buscarFiltros(); // Llamar a la función de búsqueda
    }
}
// Asignar el evento de tecla presionada a los campos de entrada relevantes
document.getElementById("productId").addEventListener("keypress", handleKeyPress);
document.getElementById("sku").addEventListener("keypress", handleKeyPress);
document.getElementById("name").addEventListener("keypress", handleKeyPress);
document.getElementById("linea").addEventListener("keypress", handleKeyPress);
document.getElementById("sublinea").addEventListener("keypress", handleKeyPress);
document.getElementById("departamento").addEventListener("keypress", handleKeyPress);
document.getElementById("activo").addEventListener("keypress", handleKeyPress);



function limpiarFiltros() {
    const inputs = [
        "productId",
        "sku",
        "name",
        "linea",
        "sublinea",
        "departamento",
    ];
    inputs.forEach((input) => {
        document.getElementById(input).value = "";
    });
    document.getElementById("activo").value = "todos";

    buscarFiltros();
}

function checkDefault(id, defaultValue) {
    var input = document.getElementById(id);
    if (input.value === defaultValue) {
        input.value = "";
    } else if (!input.value.startsWith(defaultValue)) {
        input.value = defaultValue + input.value;
    }
}

function showPrintModal(sku, description) {
    document.getElementById("modalSku").value = sku;
    document.getElementById("modalDescription").value = description;
    $("#printModal").modal("show");
}

function submitPrintForm() {
    var printForm = document.getElementById("printForm");
    var formData = new FormData(printForm);

    fetch(printLabelUrl, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]')
                .value,
        },
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.url) {
                var iframe = document.createElement("iframe");
                iframe.style.display = "none";
                iframe.src = data.url;
                iframe.onload = function () {
                    iframe.contentWindow.print();
                };
                document.body.appendChild(iframe);
            } else {
                console.error("Error al generar el PDF");
            }
        })
        .catch((error) => console.error("Error:", error));
}

function validateInput(input, maxLength) {
    if (!/^\d*$/.test(input.value)) {
        input.value = input.value.replace(/[^\d]/g, "");
    }
    if (input.value.length > maxLength) {
        input.value = input.value.slice(0, maxLength);
    }
}

function showPrintModal(sku, description) {
    document.getElementById("modalSku").value = sku;
    document.getElementById("modalDescription").value = description;
    document.getElementById("modalSkuInput").value = sku;
    document.getElementById("modalDescriptionInput").value = description;
    $("#printModal").modal("show");
}

function showPrintModalWithPrice(sku, description, precioBase, productId) {
    document.getElementById("modalSkuWithPrice").value = sku;
    document.getElementById("modalDescriptionWithPrice").value = description;
    document.getElementById("modalPrecioBase").value = precioBase;
    document.getElementById("modalProductId").value = productId;
    document.getElementById("modalSkuInputWithPrice").value = sku;
    document.getElementById("modalDescriptionInputWithPrice").value =
        description;
    document.getElementById("modalPrecioBaseInput").value = precioBase;

    // Limpiar y deshabilitar el combobox mientras se cargan los datos
    let umvSelect = document.getElementById("umvSelect");
    umvSelect.innerHTML = "<option>Cargando...</option>";
    umvSelect.disabled = true;

    // Realizar la solicitud para obtener las UMV disponibles y la UMB
    fetch(`/get-umv/${productId}`)
        .then((response) => response.json())
        .then((data) => {
            // Limpiar y habilitar el combobox
            umvSelect.innerHTML = "";
            umvSelect.disabled = false;

            // Añadir la opción para la unidad de medida base (UMB)
            let option = document.createElement("option");
            option.value = "";
            option.text = data.umBase;
            umvSelect.appendChild(option);

            // Añadir las UMV disponibles
            data.umvList.forEach((umv) => {
                let option = document.createElement("option");
                option.value = umv;
                option.text = umv;
                umvSelect.appendChild(option);
            });

            // Función para actualizar el precio en el modal
            function updatePrice() {
                let selectedUmv = umvSelect.value;
                let basePrice = parseFloat(precioBase);
                let adjustedPrice = basePrice;

                if (selectedUmv) {
                    let conversionFactor = data.umvFactors[selectedUmv];
                    if (conversionFactor) {
                        adjustedPrice = basePrice * conversionFactor;
                    }
                }

                if (data.impuesto == 1) {
                    adjustedPrice *= 1.16; // Aplicar IVA si corresponde
                }

                document.getElementById("modalPrecioBaseInput").value =
                    adjustedPrice.toFixed(2);
            }

            // Actualizar el precio inicialmente
            updatePrice();

            // Actualizar el precio ajustado si se selecciona una UMV diferente a la UMB
            umvSelect.addEventListener("change", updatePrice);
        })
        .catch((error) => {
            console.error("Error al cargar las UMV:", error);
            umvSelect.innerHTML = "<option>Error al cargar</option>";
        });

    $("#printModalWithPrice").modal("show");
}

function showPrintModalWithPrice(sku, description, precioBase, productId) {
    document.getElementById("modalSkuWithPrice").value = sku;
    document.getElementById("modalDescriptionWithPrice").value = description;
    document.getElementById("modalPrecioBase").value = precioBase;
    document.getElementById("modalProductId").value = productId;
    document.getElementById("modalSkuInputWithPrice").value = sku;
    document.getElementById("modalDescriptionInputWithPrice").value =
        description;
    document.getElementById("modalPrecioBaseInput").value = precioBase;

    let umvSelect = document.getElementById("umvSelect");
    umvSelect.innerHTML = "<option>Cargando...</option>";
    umvSelect.disabled = true;

    fetch(`/get-umv/${productId}`)
        .then((response) => response.json())
        .then((data) => {
            umvSelect.innerHTML = "";
            umvSelect.disabled = false;

            let option = document.createElement("option");
            option.value = "";
            option.text = data.umBase;
            umvSelect.appendChild(option);

            data.umvList.forEach((umv) => {
                let option = document.createElement("option");
                option.value = umv;
                option.text = umv;
                umvSelect.appendChild(option);
            });

            function updatePrice() {
                let selectedUmv = umvSelect.value;
                let basePrice = parseFloat(precioBase);
                let adjustedPrice = basePrice;

                if (selectedUmv) {
                    let conversionFactor = data.umvFactors[selectedUmv];
                    if (conversionFactor) {
                        adjustedPrice = basePrice * conversionFactor;
                    }
                }

                if (data.impuesto == 1) {
                    adjustedPrice *= 1.16; // Aplicar IVA si corresponde
                }

                document.getElementById("modalPrecioBaseInput").value = (
                    Math.floor(adjustedPrice * 100) / 100
                ).toFixed(2);
            }

            updatePrice();
            umvSelect.addEventListener("change", updatePrice);
        })
        .catch((error) => {
            console.error("Error al cargar las UMV:", error);
            umvSelect.innerHTML = "<option>Error al cargar</option>";
        });

    $("#printModalWithPrice").modal("show");
}
function submitPrintFormWithPrice() {
    var printForm = document.getElementById("printFormWithPrice");
    var formData = new FormData(printForm);

    console.log("Datos enviados:", Object.fromEntries(formData.entries()));

    fetch(printLabelUrlWithPrice, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]')
                .value,
            Accept: "application/json",
            "Content-Type": "application/json",
        },
        body: JSON.stringify(Object.fromEntries(formData.entries())),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Error en la respuesta del servidor");
            }
            return response.json();
        })
        .then((data) => {
            if (data.url) {
                var iframe = document.createElement("iframe");
                iframe.style.display = "none";
                iframe.src = data.url;
                iframe.onload = function () {
                    iframe.contentWindow.print();
                };
                document.body.appendChild(iframe);
            } else {
                console.error("Error al generar el PDF");
            }
        })
        .catch((error) => {
            console.error("Error:", error);
        });
}
