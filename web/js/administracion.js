let codigoSeleccionado = null; // Para almacenar el código de la cita seleccionada

document.addEventListener("DOMContentLoaded", () => {
    const calendarContainer = document.getElementById("calendario-flatpickr");

    flatpickr(calendarContainer, {
        inline: true,
        locale: "es",
        defaultDate: "today",
        onChange: function(selectedDates, dateStr) {
            if (!dateStr) return;
            cargarCitas(dateStr);
        }
    });
});

function cargarCitas(fecha) {
    const container = document.getElementById("user-container");
    container.innerHTML = ""; // Limpiar anteriores

    fetch(`server/database.php?citasFecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === "success" && data.citas.length > 0) {
                data.citas.forEach(cita => {
                    const div = document.createElement("div");
                    div.className = "user-info-adm";
                    div.innerHTML = `
                        <img src="imgs/user.png" alt="Usuario" class="user-img-adm">
                        <p class="user-time-adm">${cita.hora} hrs</p>
                    `;

                    div.addEventListener("click", () => {
                        document.getElementById("fecha-cita").innerText = fecha;
                        document.getElementById("nombre-cliente").innerText = cita.nombre;
                        document.getElementById("lugar-cita").innerText = "Sucursal Principal";
                        document.getElementById("hora-cita").innerText = cita.hora;
                        document.getElementById("razon-cita").innerText = cita.servicio;
                        document.getElementById("costo-cita").innerText = `$${parseFloat(cita.costo).toFixed(2)}`;

                        // Guardar código seleccionado
                        codigoSeleccionado = cita.codigo;
                    });

                    container.appendChild(div);
                });
            } else {
                container.innerHTML = `<h2 class="empty-title-adm">No hay citas para esta fecha</h2>`;
            }
        })
        .catch(err => {
            console.error("Error al cargar citas:", err);
            container.innerHTML = `<h2 class="empty-title-adm">Error al cargar citas</h2>`;
        });
}

document.getElementById("recordar-cita").addEventListener("click", () => {
    if (!codigoSeleccionado) {
        alert("Primero selecciona una cita.");
        return;
    }

    fetch("server/sendEmail.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            codigo: codigoSeleccionado
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
    })
    .catch(err => {
        console.error("Error al enviar correo:", err);
        alert("Error al enviar el recordatorio.");
    });
});
