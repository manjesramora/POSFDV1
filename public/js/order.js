$(document).ready(function () {
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
                    let dropdown = field === "CNCDIRID" ? $("#idDropdown") : $("#nameDropdown");
                    dropdown.empty().show();

                    data.forEach((item, index) => {
                        dropdown.append(
                            `<div class="dropdown-item" data-id="${item.CNCDIRID}" data-name="${item.CNCDIRNOM}" tabindex="${index}">${item.CNCDIRID} - ${item.CNCDIRNOM}</div>`
                        );
                    });

                    // Focus on the first item in the dropdown
                    dropdown.find(".dropdown-item").first().addClass("active");
                },
            });
        } else {
            $("#idDropdown, #nameDropdown").hide();
        }
    });

    // Selección de un proveedor del dropdown
    $(document).on("click", ".dropdown-item", function () {
        selectProvider($(this));
    });

    // Handle keyboard navigation for dropdown items
    $("#CNCDIRID, #CNCDIRNOM").on("keydown", function (e) {
        let dropdown = $(this).attr("id") === "CNCDIRID" ? $("#idDropdown") : $("#nameDropdown");

        if (dropdown.is(":visible")) {
            let activeItem = dropdown.find(".dropdown-item.active");

            if (e.key === "ArrowDown") {
                e.preventDefault();
                let nextItem = activeItem.next(".dropdown-item");
                if (nextItem.length > 0) {
                    activeItem.removeClass("active");
                    nextItem.addClass("active");
                }
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                let prevItem = activeItem.prev(".dropdown-item");
                if (prevItem.length > 0) {
                    activeItem.removeClass("active");
                    prevItem.addClass("active");
                }
            } else if (e.key === "Enter") {
                e.preventDefault();
                selectProvider(activeItem);
            } else if (e.key === "Escape") {
                dropdown.hide();
            }
        }
    });

    // Function to select a provider
    function selectProvider(item) {
        let id = item.data("id");
        let name = item.data("name");
        $("#CNCDIRID").val(id);
        $("#CNCDIRNOM").val(name);
        $("#idDropdown, #nameDropdown").hide();
    }

    // Limpiar campos
    function limpiarCampos() {
        $("#CNCDIRID, #CNCDIRNOM").val("");
        $("#idDropdown, #nameDropdown").hide();
    }

    // Limpiar campos adicionales
    function limpiarCamposAdicionales() {
        document.getElementById("ACMROIDOC").value = "";
        document.getElementById("CNCDIRID").value = "";
        document.getElementById("CNCDIRNOM").value = "";
        document.getElementById("start_date").value = "";
        document.getElementById("end_date").value = "";
        $("#idDropdown, #nameDropdown").hide();
    }

    // Ordenar tabla
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
});
// Clear all filter input fields
function limpiarCampos() {
    // Clear the text inputs
    document.getElementById("ACMVOIDOC").value = "";
    document.getElementById("CNCDIRID").value = "";
    document.getElementById("CNCDIRNOM").value = "";

    // Clear the date inputs
    document.getElementById("start_date").value = "";
    document.getElementById("end_date").value = "";

    // Hide dropdowns if visible
    $("#idDropdown, #nameDropdown").hide();

    // Submit the form to reset filters
    document.getElementById("filterForm").submit();
}
