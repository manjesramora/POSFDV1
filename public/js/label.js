let sortingInProgress = false; // Flag para evitar múltiples solicitudes

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
    var activo = document.getElementById("activo").value;
    var activo = document.getElementById("activo").value;

    var hasFilters =
        productId || sku || name || linea || sublinea || departamento || activo;

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
    url.searchParams.delete("activo");
    url.searchParams.delete("page"); // Reiniciar el paginado a la página 1

    // Establecer los nuevos parámetros de búsqueda
    if (productId) url.searchParams.set("productId", productId);
    if (sku) url.searchParams.set("sku", sku);
    if (name) url.searchParams.set("name", name);
    if (linea) url.searchParams.set("linea", linea);
    if (sublinea) url.searchParams.set("sublinea", sublinea);
    if (departamento) url.searchParams.set("departamento", departamento);
    if (activo) url.searchParams.set("activo", activo);

    // Actualizar la URL del navegador
    window.history.pushState({}, "", url);

    // Realizar la solicitud fetch para actualizar la tabla y la paginación
    fetch(url)
        .then((response) => response.text())
        .then((html) => {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, "text/html");
            var newContent = doc.getElementById("proveedorTable").innerHTML;
            var newPagination =
                doc.getElementById("pagination-links").innerHTML;

            document.getElementById("proveedorTable").innerHTML = newContent;
            document.getElementById("pagination-links").innerHTML =
                newPagination;

            // Verificar si no hay resultados
            if (hasFilters && !newContent.trim()) {
                document.getElementById("no-results-message").style.display =
                    "block";
            } else {
                document.getElementById("no-results-message").style.display =
                    "none";
            }

            // Reattach event listeners for pagination links
            reattachPaginationEventListeners();
        })
        .catch((error) => console.error("Error en la solicitud fetch:", error))
        .finally(() => hideLoading()); // Ocultar la animación de "Cargando" al finalizar
}

// Manejar la ordenación sin recargar la página
document.querySelectorAll(".sortable-column").forEach((column) => {
    column.addEventListener("click", function (e) {
        e.preventDefault();
        if (sortingInProgress) return; // Evitar múltiples solicitudes
        sortingInProgress = true; // Establecer el flag

        showLoading();

        // Obtener la columna y la dirección de ordenación
        let selectedColumn = e.currentTarget.getAttribute("data-column");
        let currentDirection = e.currentTarget.getAttribute("data-direction");

        // Alternar la dirección de orden si es la misma columna
        let direction = currentDirection === "asc" ? "desc" : "asc";

        // Actualizar la dirección de orden en el elemento
        e.currentTarget.setAttribute("data-direction", direction);

        // Construir la nueva URL con los filtros actuales
        let url = new URL(window.location.href);
        url.searchParams.set("sort", selectedColumn);
        url.searchParams.set("direction", direction);

        // Actualizar la URL del navegador para mantener el estado
        window.history.pushState({}, "", url);

        fetch(url)
            .then((response) => response.text())
            .then((html) => {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, "text/html");
                document.getElementById("proveedorTable").innerHTML =
                    doc.getElementById("proveedorTable").innerHTML;
                document.getElementById("pagination-links").innerHTML =
                    doc.getElementById("pagination-links").innerHTML;

                // Volver a adjuntar los event listeners después de actualizar el contenido
                reattachPaginationEventListeners();
            })
            .catch((error) =>
                console.error("Error en la solicitud fetch:", error)
            )
            .finally(() => {
                sortingInProgress = false; // Resetear el flag
                hideLoading();
            });
    });
});

// Reattach event listeners for pagination links
function reattachPaginationEventListeners() {
    document.querySelectorAll("#pagination-links a").forEach(function (link) {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            showLoading(); // Mostrar la animación de "Cargando" al hacer clic en un enlace de paginación

            fetch(event.target.href)
                .then((response) => response.text())
                .then((html) => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, "text/html");
                    document.getElementById("proveedorTable").innerHTML =
                        doc.getElementById("proveedorTable").innerHTML;
                    document.getElementById("pagination-links").innerHTML =
                        doc.getElementById("pagination-links").innerHTML;

                    // Volver a adjuntar los event listeners después de actualizar el contenido
                    reattachPaginationEventListeners();
                })
                .catch((error) =>
                    console.error("Error en la solicitud fetch:", error)
                )
                .finally(() => hideLoading());
        });
    });
}

// Función para manejar el evento de tecla presionada en los campos de entrada
function handleKeyPress(event) {
    if (event.keyCode === 13) {
        // Código 13 es Enter
        event.preventDefault(); // Prevenir el comportamiento por defecto (enviar formulario)
        buscarFiltros(); // Llamar a la función de búsqueda
    }
}

// Asignar el evento de tecla presionada a los campos de entrada relevantes
document
    .getElementById("productId")
    .addEventListener("keypress", handleKeyPress);
document.getElementById("sku").addEventListener("keypress", handleKeyPress);
document.getElementById("name").addEventListener("keypress", handleKeyPress);
document.getElementById("linea").addEventListener("keypress", handleKeyPress);
document
    .getElementById("sublinea")
    .addEventListener("keypress", handleKeyPress);
document
    .getElementById("departamento")
    .addEventListener("keypress", handleKeyPress);
document.getElementById("activo").addEventListener("keypress", handleKeyPress);

// Limpia los campos de búsqueda restableciendo a sus valores por defecto
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
}

// Valida la entrada de los campos numéricos (solo permite dígitos y un máximo de caracteres)
function validateInput(input, maxLength) {
    if (!/^\d*$/.test(input.value)) {
        input.value = input.value.replace(/[^\d]/g, ""); // Reemplazar todo lo que no sea número
    }
    if (input.value.length > maxLength) {
        input.value = input.value.slice(0, maxLength);  // Limitar longitud máxima
    }
}


// Función que abre un modal de impresión sin precios
function showPrintModal(sku, description) {
    // Establecer los valores del modal con los datos del producto
    document.getElementById("modalSku").value = sku;
    document.getElementById("modalDescription").value = description;
    document.getElementById("modalSkuInput").value = sku;
    document.getElementById("modalDescriptionInput").value = description;
    
    // Mostrar el modal de impresión
    $("#printModal").modal("show");
}

// Función que abre un modal de impresión con precios
function showPrintModalWithPrice(sku, description, precioBase, productId) {
    document.getElementById("modalSkuWithPrice").value = sku;
    // Establecer los valores del modal con los datos del producto, incluyendo el precio
    document.getElementById("modalDescriptionWithPrice").value = description;
    document.getElementById("modalPrecioBase").value = precioBase;
    document.getElementById("modalProductId").value = productId;
    document.getElementById("modalSkuInputWithPrice").value = sku;
    document.getElementById("modalDescriptionInputWithPrice").value =
        description;
    document.getElementById("modalPrecioBaseInput").value = precioBase;

    // Deshabilitar temporalmente el selector UMV mientras se cargan los datos
    let umvSelect = document.getElementById("umvSelect");
    umvSelect.innerHTML = "<option>Cargando...</option>";
    umvSelect.disabled = true;

    // Hacer la solicitud para obtener las unidades de medida variables (UMV) del producto
    fetch(`/get-umv/${productId}`)
        .then((response) => response.json()) // Convertir la respuesta en JSON
        .then((data) => {
            // Limpiar el selector de UMV y habilitarlo
            umvSelect.innerHTML = "";
            umvSelect.disabled = false;

            // Agregar la UM base como primera opción en el selector
            let option = document.createElement("option");
            option.value = "";
            option.text = data.umBase;
            umvSelect.appendChild(option);

            // Agregar las demás UMV al selector
            data.umvList.forEach((umv) => {
                let option = document.createElement("option");
                option.value = umv;
                option.text = umv;
                umvSelect.appendChild(option);
            });

            // Función interna para actualizar el precio basado en la UMV seleccionada
            function updatePrice() {
                let selectedUmv = umvSelect.value;      // Obtener UMV seleccionada
                let basePrice = parseFloat(precioBase); // Convertir el precio base a número
                let adjustedPrice = basePrice;          // Precio ajustado basado en la UMV

                // Ajustar el precio si hay un factor de conversión para la UMV seleccionada
                if (selectedUmv) {
                    let conversionFactor = data.umvFactors[selectedUmv];
                    if (conversionFactor) {
                        adjustedPrice = basePrice * conversionFactor;
                    }
                }

                // Aplicar IVA si es necesario (impuesto == 1)
                if (data.impuesto == 1) {
                    adjustedPrice *= 1.16; // 16% de IVA
                }

                // Mostrar el precio ajustado con 2 decimales
                document.getElementById("modalPrecioBaseInput").value = (
                    Math.floor(adjustedPrice * 100) / 100
                ).toFixed(2);
            }

            // Inicializar el precio cuando se carga la información
            updatePrice();

            // Actualizar el precio cuando el usuario cambie la UMV seleccionada
            umvSelect.addEventListener("change", updatePrice);
        })
        .catch((error) => {
            console.error("Error al cargar las UMV:", error);
            // Mostrar mensaje de error si ocurre un problema al cargar las UMV
            umvSelect.innerHTML = "<option>Error al cargar</option>";
        });

    // Mostrar el modal de impresión con precio
    $("#printModalWithPrice").modal("show");
}

// Función que maneja el envío del formulario de impresión sin precios
function submitPrintForm() {
    const quantityInput = document.getElementById("quantity");      // Campo de cantidad
    const quantityError = document.getElementById("quantityError"); // Mensaje de error
    const quantity = parseInt(quantityInput.value, 10);             // Convertir la cantidad ingresada a número

    // Ocultar el mensaje de error
    quantityError.style.display = "none";
    quantityError.textContent = "";

    // Validar que la cantidad sea un número válido mayor que 0
    if (isNaN(quantity) || quantity <= 0) {
        quantityError.textContent = "Por favor, ingrese una cantidad válida.";
        quantityError.style.display = "block";
        return;
    }

    // Validar que la cantidad no exceda el límite máximo de etiquetas
    const MAX_LABELS = 100;
    if (quantity > MAX_LABELS) {
        quantityError.textContent = `La cantidad máxima de etiquetas es ${MAX_LABELS}.`;
        quantityError.style.display = "block";
        return;
    }

    // Mostrar la animación de "Cargando"
    showLoading();

    // Obtener el formulario y convertirlo en FormData
    var printForm = document.getElementById("printForm");
    var formData = new FormData(printForm);

    // Enviar los datos del formulario para generar las etiquetas
    fetch(printLabelUrl, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value, // Token CSRF para proteger contra ataques
        },
        body: formData, // Enviar los datos del formulario
    })
    .then((response) => response.json()) // Parsear la respuesta como JSON
    .then((data) => {
        // Ocultar la animación de "Cargando"
        hideLoading();

        // Si se genera un URL, crear un iframe invisible para imprimir
        if (data.url) {
            var iframe = document.createElement("iframe");
            iframe.style.display = "none";
            iframe.src = data.url;                // Establecer la URL del PDF
            iframe.onload = function () {
                iframe.contentWindow.print();    // Imprimir cuando el PDF esté cargado
            };
            document.body.appendChild(iframe);  // Agregar el iframe al DOM
        } else {
            console.error("Error al generar el PDF");  // Manejar error si no se genera el PDF
        }
    })
    .catch((error) => {
        console.error("Error:", error); // Manejo de errores
        // Ocultar la animación de "Cargando" en caso de error
        hideLoading();
    });
}



// Función que maneja el envío del formulario de impresión con precios
function submitPrintFormWithPrice() {
    var printForm = document.getElementById("printFormWithPrice");  // Obtener formulario con precios
    var formData = new FormData(printForm);                         // Convertir formulario en FormData

    // Enviar el formulario con los datos convertidos a JSON
    fetch(printLabelUrlWithPrice, {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]') // Token CSRF
                .value,
            Accept: "application/json",
            "Content-Type": "application/json",
        },
        body: JSON.stringify(Object.fromEntries(formData.entries())),  // Convertir FormData a JSON
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Error en la respuesta del servidor"); // Lanzar error si la respuesta no es exitosa
            }
            return response.json(); // Convertir respuesta a JSON
        })
        .then((data) => {
            // Si se genera un URL, crear un iframe invisible para imprimir
            if (data.url) {
                var iframe = document.createElement("iframe");
                iframe.style.display = "none";
                iframe.src = data.url;              // Establecer la URL del PDF
                iframe.onload = function () {
                    iframe.contentWindow.print();  // Imprimir cuando el PDF esté cargado
                };
                document.body.appendChild(iframe); // Agregar el iframe al DOM
            } else { 
                console.error("Error al generar el PDF"); // Manejar error si no se genera el PDF
            }
        })
        .catch((error) => {
            console.error("Error:", error); // Manejar cualquier error que ocurra
        });
}


// Asignar evento al campo de cantidad para enviar el formulario al presionar Enter
document.addEventListener("DOMContentLoaded", function () {
    const quantityInput = document.getElementById("quantity");

    if (quantityInput) {
        // Agregar evento de teclado al campo de cantidad
        quantityInput.addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                event.preventDefault(); // Prevenir comportamiento por defecto del Enter
                submitPrintForm();      // Llamar a la función de envío del formulario
            }
        });
    }
});
