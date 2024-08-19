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
    // Obtener los datos del contenedor
    var modalsData = document.getElementById('modalsData');
    var errorsExist = modalsData.getAttribute('data-errors') === 'true';
    var permissionId = modalsData.getAttribute('data-permission-id');

    // Mostrar el modal de agregar si hay errores y no hay permissionId
    if (errorsExist && !permissionId) {
        var addPermissionModal = new bootstrap.Modal(document.getElementById('addPermissionModal'));
        addPermissionModal.show();
    }

    // Mostrar el modal de editar si hay errores y un permissionId
    if (errorsExist && permissionId) {
        var editPermissionModal = new bootstrap.Modal(document.getElementById('editPermissionModal' + permissionId));
        editPermissionModal.show();
    }
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