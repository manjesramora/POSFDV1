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

document.addEventListener('DOMContentLoaded', function () {
    // Manejar modal de agregar permiso
    var addPermissionModalElement = document.getElementById('addPermissionModal');
    var addErrorsExist = addPermissionModalElement.getAttribute('data-errors') === 'true';

    if (addErrorsExist) {
        var addPermissionModal = new bootstrap.Modal(addPermissionModalElement);
        addPermissionModal.show();
    }

    // Limpiar mensajes de error y valores al cerrar modal de agregar
    addPermissionModalElement.addEventListener('hidden.bs.modal', function () {
        var form = addPermissionModalElement.querySelector('form');
        
        // Limpiar todos los campos de texto y textarea
        form.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(function(input) {
            input.value = '';
        });

        // Limpia los mensajes de error
        form.querySelectorAll('.text-danger').forEach(function(errorSpan) {
            errorSpan.textContent = '';
        });
    });

    // Manejar modales de edición de permiso
    document.querySelectorAll('[id^=editPermissionModal]').forEach(function (modalElement) {
        var editErrorsExist = modalElement.getAttribute('data-errors') === 'true';

        if (editErrorsExist) {
            var editPermissionModal = new bootstrap.Modal(modalElement);
            editPermissionModal.show();
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
            var permissionName = modalElement.querySelector('.permission-name');
            var description = modalElement.querySelector('input[name="description"]');
            
            // Recargar los datos originales desde los atributos de datos
            permissionName.value = form.getAttribute('data-original-name');
            description.value = form.getAttribute('data-original-description');
        });
    });
});

document.querySelectorAll('.permission-name').forEach(function (nameField) {
    nameField.addEventListener('input', function () {
        const errorSpan = nameField.nextElementSibling;
        
        if (nameField.validity.patternMismatch) {
            errorSpan.textContent = 'El nombre del permiso solo puede contener letras (sin acentos).';
        } else {
            errorSpan.textContent = ''; // Limpiar el mensaje de error si todo es válido
        }
    });
});