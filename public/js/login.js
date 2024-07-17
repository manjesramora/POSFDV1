document.addEventListener("DOMContentLoaded", function () {
    const loginRoute = window.routes.login;
    const changePasswordRoute = window.routes.changePassword;
    const indexRoute = window.routes.index;

    // Función para eliminar todos los mensajes anteriores
    function clearMessages() {
        const existingMessages = document.querySelectorAll('.alert.text-center');
        existingMessages.forEach(el => el.remove());
    }

    // Función para mostrar mensaje de error
    function showErrorMessage(message) {
        clearMessages();

        // Crear nuevo elemento para el mensaje de error
        const errorElement = document.createElement('div');
        errorElement.className = 'alert alert-danger text-center mt-3';
        errorElement.textContent = message;

        // Insertar mensaje de error después del contenedor .center
        const centerDiv = document.querySelector('.center');
        centerDiv.insertAdjacentElement('afterend', errorElement);
    }

    // Función para mostrar mensaje de éxito
    function showSuccessMessage(message) {
        clearMessages();

        // Crear nuevo elemento para el mensaje de éxito
        const successElement = document.createElement('div');
        successElement.className = 'alert alert-success text-center mt-3';
        successElement.textContent = message;

        // Insertar mensaje de éxito después del contenedor .center
        const centerDiv = document.querySelector('.center');
        centerDiv.insertAdjacentElement('afterend', successElement);
    }

    document.getElementById("loginForm").addEventListener("submit", function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        const csrfTokenInput = document.querySelector('input[name="_token"]');
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : null;

        fetch(loginRoute, {
            method: "POST",
            body: formData,
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => Promise.reject(data));
                }
                return response.json();
            })
            .then(data => {
                if (data.changePassword) {
                    document.getElementById("modalUsername").value = data.username;
                    new bootstrap.Modal(document.getElementById("passwordModal")).show();
                } else {
                    window.location.href = indexRoute;
                }
            })
            .catch(data => {
                showErrorMessage(data.error);
            });
    });

    document.getElementById("changePasswordForm").addEventListener("submit", function (event) {
        event.preventDefault();
        const newPassword = document.getElementById("newPassword").value;
        const confirmPassword = document.getElementById("confirmPassword").value;

        if (newPassword === "Ferre01@") {
            document.getElementById("passwordError").textContent = "No se puede utilizar la contraseña genérica.";
            return;
        }

        const username = document.getElementById("modalUsername").value;
        const csrfTokenInput = document.querySelector('input[name="_token"]');
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : null;

        fetch(changePasswordRoute, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                username: username,
                newPassword: newPassword,
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage('La contraseña se cambió correctamente.');
                    // Limpiar los campos del formulario de inicio de sesión
                    document.querySelector('input[name="username"]').value = '';
                    document.querySelector('input[name="password"]').value = '';
                    // Limpiar los campos del modal de cambio de contraseña
                    document.getElementById("modalUsername").value = '';
                    document.getElementById("newPassword").value = '';
                    document.getElementById("confirmPassword").value = '';
                    $('#passwordModal').modal('hide');
                } else {
                    document.getElementById("passwordError").textContent = "Error al cambiar la contraseña.";
                }
            });
    });

    $('#passwordModal').on('shown.bs.modal', function () {
        const passwordInput = document.getElementById("newPassword");
        const confirmPasswordInput = document.getElementById("confirmPassword");
        const passwordError = document.getElementById("passwordError");
        const passwordRequirements = document.getElementById("passwordRequirements");

        if (passwordInput && confirmPasswordInput && passwordError && passwordRequirements) {
            passwordInput.addEventListener("input", function () {
                const password = this.value;
                let requirementsMet = {
                    mayuscula: /[A-Z]/.test(password),
                    caracterEspecial: /[!@#$%^&*(),.?":{}|<>]/.test(password),
                    numero: /\d/.test(password),
                    minimoCaracteres: password.length >= 8,
                };

                const requirementsList = Object.keys(requirementsMet).map(function (key) {
                    let icon = requirementsMet[key] ? "✔️" : "❌";
                    let color = requirementsMet[key] ? "text-muted" : "";
                    let translatedKey = "";
                    switch (key) {
                        case "mayuscula":
                            translatedKey = "Mayúscula";
                            break;
                        case "caracterEspecial":
                            translatedKey = "Carácter especial";
                            break;
                        case "numero":
                            translatedKey = "Número";
                            break;
                        case "minimoCaracteres":
                            translatedKey = "Al menos 8 caracteres";
                            break;
                        default:
                            translatedKey = key;
                    }

                    return `<li class="${color}">${icon} ${translatedKey}</li>`;
                });

                passwordRequirements.innerHTML = `
                    <p>Requisitos de la contraseña:</p>
                    <ul style="list-style-type: none;">${requirementsList.join("")}</ul>
                `;
            });
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('error-message');

    if (loginForm && errorMessage) {
        loginForm.addEventListener('submit', function () {
            errorMessage.style.display = 'none';
        });
    }
});

$(document).ready(function() {
    $(".toggle-password").click(function() {
        $(this).toggleClass("fa-eye fa-eye-slash");
        var input = $(this).siblings("input");
        if (input.attr("type") === "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
        // Toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Toggle the eye / eye slash icon
        if (type === 'password') {
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        } else {
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        }
    });
});
