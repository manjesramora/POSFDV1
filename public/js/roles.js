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

document.addEventListener('DOMContentLoaded', function () {
    // Manejar modal de agregar
    var addRoleModalElement = document.getElementById('addRoleModal');
    var addErrorsExist = addRoleModalElement.getAttribute('data-errors') === 'true';

    if (addErrorsExist) {
        var addRoleModal = new bootstrap.Modal(addRoleModalElement);
        addRoleModal.show();
    }

    // Limpiar mensajes de error, valores de inputs y checkboxes al cerrar modal de agregar
    addRoleModalElement.addEventListener('hidden.bs.modal', function () {
        var form = addRoleModalElement.querySelector('form');
        
        // Limpiar todos los campos de texto y textarea
        form.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(function(input) {
            input.value = '';
        });

        // Deseleccionar todos los checkboxes
        form.querySelectorAll('.form-check-input').forEach(function(input) {
            input.checked = false;
        });

        // Limpia los mensajes de error
        form.querySelectorAll('.text-danger').forEach(function(errorSpan) {
            errorSpan.textContent = '';
        });
    });

    // Manejar modales de edición
    document.querySelectorAll('[id^=editRoleModal]').forEach(function (modalElement) {
        var editErrorsExist = modalElement.getAttribute('data-errors') === 'true';

        if (editErrorsExist) {
            var editRoleModal = new bootstrap.Modal(modalElement);
            editRoleModal.show();
        }

        // Limpiar solo mensajes de error al cerrar modal de edición
        modalElement.addEventListener('hidden.bs.modal', function () {
            var form = modalElement.querySelector('form');
            form.querySelectorAll('.text-danger').forEach(function(errorSpan) {
                errorSpan.textContent = ''; // Limpia los mensajes de error
            });
        });

        // Recargar datos al volver a abrir el modal
        modalElement.addEventListener('show.bs.modal', function () {
            var form = modalElement.querySelector('form');
            var roleName = modalElement.querySelector('.role-name');
            var description = modalElement.querySelector('#description');
            
            // Recargar los datos desde el backend (opcionalmente, podrías hacer una llamada AJAX aquí)
            roleName.value = form.getAttribute('data-original-name');
            description.value = form.getAttribute('data-original-description');
            
            // Marcar los permisos correctamente
            form.querySelectorAll('.form-check-input').forEach(function(input) {
                input.checked = form.getAttribute('data-original-permissions').split(',').includes(input.value);
            });
        });
    });
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
