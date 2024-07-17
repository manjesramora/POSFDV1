// Enviar el formulario cuando se presiona Enter en el campo de búsqueda
document
    .getElementById("searchPermission")
    .addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            document.getElementById("searchForm").submit();
        }
    });

// Limpiar los campos de búsqueda y filtros
function limpiarCampos() {
    document.getElementById("searchPermission").value = "";
    document.getElementById("searchForm").submit();
}
