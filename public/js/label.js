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

function showPrintModalWithPrice(sku, description, precioBase) {
    document.getElementById("modalSkuWithPrice").value = sku;
    document.getElementById("modalDescriptionWithPrice").value = description;
    document.getElementById("modalPrecioBase").value = precioBase;
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
