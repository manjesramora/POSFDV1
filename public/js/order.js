// Autocompletado por ID y por Nombre
$("#CNCDIRID, #CNCDIRNOM").on("input", function () {
    let query = $(this).val();
    let field = $(this).attr("id") === "CNCDIRID" ? "CNCDIRID" : "CNCDIRNOM";

    if (query.length >= 3) {
        $.ajax({
            url: "/providers/autocomplete",
            type: "GET",
            data: { query: query, field: field },
            success: function (data) {
                let dropdown =
                    field === "CNCDIRID"
                        ? $("#idDropdown")
                        : $("#nameDropdown");
                dropdown.empty().show();

                data.forEach((item) => {
                    dropdown.append(
                        `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                    );
                });
            },
        });
    } else {
        $("#idDropdown, #nameDropdown").hide();
    }
});

// Selección de un proveedor del dropdown
$(document).on("click", ".dropdown-item", function () {
    let id = $(this).data("id");
    let name = $(this).data("name");
    $("#CNCDIRID").val(id);
    $("#CNCDIRNOM").val(name);
    $("#idDropdown, #nameDropdown").hide();
});

// Limpiar campos
function limpiarCampos() {
    $("#CNCDIRID, #CNCDIRNOM").val("");
    $("#idDropdown, #nameDropdown").hide();
}

function limpiarCampos() {
    document.getElementById("ACMROIDOC").value = "";
    document.getElementById("CNCDIRID").value = "";
    document.getElementById("CNCDIRNOM").value = "";
    document.getElementById("start_date").value = "";
    document.getElementById("end_date").value = "";
}

function sortTable(column) {
    let currentUrl = new URL(window.location.href);
    let currentSortColumn = currentUrl.searchParams.get("sortColumn");
    let currentSortDirection = currentUrl.searchParams.get("sortDirection");

    let newSortDirection = "asc";
    if (currentSortColumn === column && currentSortDirection === "asc") {
        newSortDirection = "desc";
    }

    currentUrl.searchParams.set("sortColumn", column);
    currentUrl.searchParams.set("sortDirection", newSortDirection);

    window.location.href = currentUrl.toString();
}
