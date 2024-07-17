// addEmployee.js
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#addEmployeeForm");

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        
        const formData = new FormData(form);

        axios.post(form.action, formData)
            .then(response => {
                // Aquí puedes manejar la respuesta del servidor, por ejemplo, mostrar un mensaje de éxito
                console.log(response.data);
                // Cerrar el modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal'));
                modal.hide();
            })
            .catch(error => {
                // Aquí puedes manejar errores, como mostrar mensajes de error al usuario
                console.error(error);
            });
    });
});

