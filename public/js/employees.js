document.addEventListener("DOMContentLoaded", function () {
    // Función para validar entradas de texto en tiempo real para campos sin números
    function validateTextInput(event) {
        const input = event.target;
        const errorSpan = input.nextElementSibling;

        // Permitir solo letras y espacios, sin números
        const valid = /^[a-zA-Z\s]*$/.test(input.value);
        if (!valid) {
            errorSpan.textContent = "Solo se permiten letras y espacios.";
            input.value = input.value.replace(/[^a-zA-Z\s]/g, "");
        } else {
            errorSpan.textContent = "";
        }
    }

    // Función para validar entradas de texto en tiempo real para campos solo con números
    function validateNumberInput(event) {
        const input = event.target;
        const errorSpan = input.nextElementSibling;

        // Permitir solo números
        const valid = /^[0-9]*$/.test(input.value);
        if (!valid) {
            errorSpan.textContent = "Solo se permiten números.";
            input.value = input.value.replace(/[^0-9]/g, "");
        } else {
            errorSpan.textContent = "";
        }
    }

    // Función para limpiar el mensaje de error al dejar de interactuar con el campo
    function clearErrorMessage(event) {
        const input = event.target;
        const errorSpan = input.nextElementSibling;
        errorSpan.textContent = "";
    }

    // Aplicar validación en tiempo real solo a first_name, last_name y middle_name del modal de agregar y editar
    const nameFields = ["first_name", "last_name", "middle_name"];
    nameFields.forEach(function (fieldId) {
        document
            .querySelectorAll(`#${fieldId}, [id^="edit_${fieldId}"]`)
            .forEach(function (element) {
                element.addEventListener("input", validateTextInput);
                element.addEventListener("blur", clearErrorMessage);
            });
    });

    // Aplicar validación en tiempo real solo a postal_code, phone y phone2 del modal de agregar y editar
    const numberFields = ["postal_code", "phone", "phone2"];
    numberFields.forEach(function (fieldId) {
        document
            .querySelectorAll(`#${fieldId}, [id^="edit_${fieldId}"]`)
            .forEach(function (element) {
                element.addEventListener("input", validateNumberInput);
                element.addEventListener("blur", clearErrorMessage);
            });
    });

    // Validación al enviar el formulario de agregar empleado
    document
        .getElementById("addEmployeeForm")
        .addEventListener("submit", function (event) {
            validateForm(event, "addEmployeeForm");
        });

    // Validación al enviar los formularios de editar empleado
    document
        .querySelectorAll('form[id^="editEmployeeForm"]')
        .forEach(function (form) {
            form.addEventListener("submit", function (event) {
                validateForm(event, form.id);
            });
        });

    function validateForm(event, formId) {
        const fields = [
            "first_name",
            "last_name",
            "middle_name",
            "curp",
            "rfc",
            "colony",
            "street",
            "external_number",
            "internal_number",
            "postal_code",
            "phone",
            "phone2",
            "birth",
        ];
        let validForm = true;

        fields.forEach(function (field) {
            const input = document.querySelector(
                `#${formId} [name="${field}"]`
            );
            if (!input) {
                console.warn(
                    `Input with name "${field}" not found in form "${formId}"`
                );
                return;
            }
            const errorSpan = input.nextElementSibling;
            if (!errorSpan) {
                console.warn(
                    `Error span not found for input "${field}" in form "${formId}"`
                );
                return;
            }

            errorSpan.textContent = "";

            if (!input.checkValidity()) {
                if (input.validity.patternMismatch) {
                    console.log(`Pattern mismatch for field: ${field}`);
                    if (field === "postal_code") {
                        errorSpan.textContent =
                            "El código postal debe consistir de 5 dígitos.";
                    } else if (field === "phone" || field === "phone2") {
                        errorSpan.textContent =
                            "El teléfono debe consistir de 10 dígitos.";
                    } else if (field === "curp") {
                        errorSpan.textContent =
                            "El CURP debe tener el formato AAAA000000HAAAAA00";
                    } else if (field === "rfc") {
                        errorSpan.textContent =
                            "El RFC debe tener el formato AAAA000000AAA";
                    } else {
                        errorSpan.textContent =
                            "Este campo tiene un formato incorrecto.";
                    }
                } else if (input.validity.valueMissing) {
                    console.log(`Value missing for field: ${field}`);
                    errorSpan.textContent = "Este campo es obligatorio.";
                } else if (input.validity.tooLong) {
                    console.log(`Too long input for field: ${field}`);
                    errorSpan.textContent =
                        "Este campo no puede tener más de " +
                        input.maxLength +
                        " caracteres.";
                }
                validForm = false;
            }
        });

        if (!validForm) {
            event.preventDefault();
        }
    }
});

function limpiarCampos() {
    $('#searchEmployee').val(''); // Limpiar el campo de búsqueda
    document.getElementById("filtersForm").submit();
}
