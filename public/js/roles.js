function filterRoles() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchRole");
    filter = input.value.toUpperCase();
    table = document.getElementById("dataTable");
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[0]; // Asumiendo que la columna 'Rol' es la primera
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}

// Enviar el formulario cuando se presiona Enter en el campo de búsqueda
document.getElementById('searchRole').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        document.getElementById('searchForm').submit();
    }
});

// Limpiar los campos de búsqueda y filtros
function limpiarCampos() {
    document.getElementById('searchRole').value = '';
    document.getElementById('searchForm').submit();
}
