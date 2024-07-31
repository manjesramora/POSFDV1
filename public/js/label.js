// Función para buscar y aplicar filtros en la tabla
function buscarFiltros() {
    let query = "";
    const inputs = [
        "productId",
        "sku",
        "name",
        "linea",
        "sublinea",
        "departamento",
    ];
    inputs.forEach((input) => {
        let value = document.getElementById(input).value;
        if (input === "linea" && value) {
            value = "LN" + value;
        }
        if (input === "sublinea" && value) {
            value = "SB" + value;
        }
        if (value) {
            query += `&${input}=${encodeURIComponent(value)}`;
        }
    });

    const activo = document.getElementById("activo").value;
    if (activo) {
        query += `&activo=${encodeURIComponent(activo)}`;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const sort = urlParams.get("sort") || "INPROD.INPRODID";
    const direction = urlParams.get("direction") || "asc";

    window.location.href = `${window.location.pathname}?sort=${sort}&direction=${direction}${query}`;
}

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
    document.getElementById("modalDescriptionInputWithPrice").value = description;
    document.getElementById("modalPrecioBaseInput").value = precioBase;

    // Limpiar y deshabilitar el combobox mientras se cargan los datos
    let umvSelect = document.getElementById("umvSelect");
    umvSelect.innerHTML = '<option>Cargando...</option>';
    umvSelect.disabled = true;

    // Realizar la solicitud para obtener las UMV disponibles y la UMB
    fetch(`/get-umv/${productId}`)
        .then(response => response.json())
        .then(data => {
            // Limpiar y habilitar el combobox
            umvSelect.innerHTML = '';
            umvSelect.disabled = false;

            // Añadir la opción para la unidad de medida base (UMB)
            let option = document.createElement("option");
            option.value = "";
            option.text = data.umBase;
            umvSelect.appendChild(option);

            // Añadir las UMV disponibles
            data.umvList.forEach(umv => {
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

                document.getElementById("modalPrecioBaseInput").value = adjustedPrice.toFixed(2);
            }

            // Actualizar el precio inicialmente
            updatePrice();

            // Actualizar el precio ajustado si se selecciona una UMV diferente a la UMB
            umvSelect.addEventListener('change', updatePrice);
        })
        .catch(error => {
            console.error('Error al cargar las UMV:', error);
            umvSelect.innerHTML = '<option>Error al cargar</option>';
        });

    $("#printModalWithPrice").modal("show");
}

function showPrintModalWithPrice(sku, description, precioBase, productId) {
    document.getElementById("modalSkuWithPrice").value = sku;
    document.getElementById("modalDescriptionWithPrice").value = description;
    document.getElementById("modalPrecioBase").value = precioBase;
    document.getElementById("modalProductId").value = productId;
    document.getElementById("modalSkuInputWithPrice").value = sku;
    document.getElementById("modalDescriptionInputWithPrice").value = description;
    document.getElementById("modalPrecioBaseInput").value = precioBase;

    let umvSelect = document.getElementById("umvSelect");
    umvSelect.innerHTML = '<option>Cargando...</option>';
    umvSelect.disabled = true;

    fetch(`/get-umv/${productId}`)
        .then(response => response.json())
        .then(data => {
            umvSelect.innerHTML = '';
            umvSelect.disabled = false;

            let option = document.createElement("option");
            option.value = "";
            option.text = data.umBase;
            umvSelect.appendChild(option);

            data.umvList.forEach(umv => {
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

                document.getElementById("modalPrecioBaseInput").value = (Math.floor(adjustedPrice * 100) / 100).toFixed(2);
            }

            updatePrice();
            umvSelect.addEventListener('change', updatePrice);
        })
        .catch(error => {
            console.error('Error al cargar las UMV:', error);
            umvSelect.innerHTML = '<option>Error al cargar</option>';
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
            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
            "Accept": "application/json",
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

   





