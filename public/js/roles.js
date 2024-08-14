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

document.addEventListener('DOMContentLoaded', function () {
    var deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
    deleteConfirmationModal.addEventListener('show.bs.modal', function (event) {
        // Botón que activó el modal
        var button = event.relatedTarget;
        // Extraer información de los atributos data-role-id y data-role-name
        var roleId = button.getAttribute('data-role-id');
        var roleName = button.getAttribute('data-role-name');

        // Actualizar el contenido del modal
        var roleNameElement = deleteConfirmationModal.querySelector('#roleName');
        roleNameElement.textContent = roleName;

        // Actualizar la acción del formulario
        var deleteRoleForm = deleteConfirmationModal.querySelector('#deleteRoleForm');
        deleteRoleForm.action = '/roles/' + roleId; // Ajusta según la ruta correcta para eliminar un rol
    });
});



$(document).ready(function() {
    if ($('#addRoleErrors ul').children().length > 0) {
        $('#addRoleModal').modal('show');
        $('#addRoleErrors').show();
    }
});


document.querySelectorAll('.role-name').forEach(function (nameField) {
    nameField.addEventListener('input', function () {
        const errorSpan = nameField.nextElementSibling;
        
        if (nameField.validity.patternMismatch) {
            errorSpan.textContent = 'El nombre del rol solo puede contener letras (sin acentos).';
        } else {
            errorSpan.textContent = ''; // Limpiar el mensaje de error si todo es válido
        }
    });
});
