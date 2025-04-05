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

    cargarPromociones();
    cargarServicios();
});

function cargarCitas(fecha) {
    const container = document.getElementById("user-container");
    container.innerHTML = "";

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
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ codigo: codigoSeleccionado })
    })
    .then(res => res.json())
    .then(data => alert(data.message))
    .catch(err => {
        console.error("Error al enviar correo:", err);
        alert("Error al enviar el recordatorio.");
    });
});

function eliminarPromocion(codigo) {
    fetch("server/database.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ codigoPromocion: codigo })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.status === "success") cargarPromociones();
    })
    .catch(err => {
        console.error("Error al eliminar la promoción:", err);
        alert("Ocurrió un error al eliminar.");
    });
}

function cargarPromociones() {
    fetch("server/database.php?listarPromociones")
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                const tbody = document.querySelector(".promo-table tbody");
                tbody.innerHTML = "";

                data.promociones.forEach(promo => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td data-id="${promo.id}">${promo.codigo}</td>
                        <td>${promo.descripcion}</td>
                        <td>${promo.descuento}%</td>
                        <td>${promo.servicio}</td>
                        <td>
                            <button class="edit-btn">Editar</button>
                            <button class="delete-btn" data-codigo="${promo.codigo}">Eliminar</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Eventos dinámicos
                document.querySelectorAll(".delete-btn").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const codigo = btn.getAttribute("data-codigo");
                        if (confirm(`¿Eliminar promoción "${codigo}"?`)) {
                            eliminarPromocion(codigo);
                        }
                    });
                });

                document.querySelectorAll(".edit-btn").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const row = btn.closest("tr");
                        const id = parseInt(row.children[0].getAttribute("data-id"));
                        const codigo = row.children[0].textContent;
                        const descripcion = row.children[1].textContent;
                        const descuento = parseFloat(row.children[2].textContent);
                        const servicio = row.children[3].textContent;

                        document.getElementById("edit-id").value = id;
                        document.getElementById("edit-codigo").value = codigo;
                        document.getElementById("edit-descripcion").value = descripcion;
                        document.getElementById("edit-descuento").value = descuento;

                        fetch("server/database.php?servicios")
                            .then(res => res.json())
                            .then(data => {
                                const select = document.getElementById("edit-servicio");
                                select.innerHTML = `<option value="">Selecciona un servicio</option>`;
                                data.servicios.forEach((s, index) => {
                                    const option = document.createElement("option");
                                    option.value = index + 1;
                                    option.textContent = s.nombre;
                                    if (s.nombre === servicio) option.selected = true;
                                    select.appendChild(option);
                                });
                                document.getElementById("modal-editar").style.display = "flex";
                            });
                    });
                });

                const cerrar = document.querySelector(".custom-close");
                const cancelar = document.getElementById("cancelar-edicion");
                const guardar = document.getElementById("guardar-edicion");

                if (cerrar && cancelar && guardar) {
                    cerrar.onclick = cancelar.onclick = () => {
                        document.getElementById("modal-editar").style.display = "none";
                    };

                    guardar.onclick = () => {
                        const id = parseInt(document.getElementById("edit-id").value);
                        const codigo = document.getElementById("edit-codigo").value;
                        const descripcion = document.getElementById("edit-descripcion").value;
                        const descuento = parseFloat(document.getElementById("edit-descuento").value);
                        const servicio_id = parseInt(document.getElementById("edit-servicio").value);

                        if (!codigo || !descripcion || isNaN(descuento) || isNaN(servicio_id)) {
                            alert("Todos los campos son obligatorios.");
                            return;
                        }

                        fetch("server/database.php", {
                            method: "PUT",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                editarPromocion: true,
                                id,
                                codigo,
                                descripcion,
                                descuento,
                                servicio_id
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            alert(data.message);
                            if (data.status === "success") {
                                document.getElementById("modal-editar").style.display = "none";
                                cargarPromociones();
                            }
                        })
                        .catch(err => {
                            console.error("Error al editar la promoción:", err);
                            alert("Ocurrió un error al editar.");
                        });
                    };
                }

            } else {
                console.warn("No se pudieron cargar promociones:", data.message);
            }
        })
        .catch(err => {
            console.error("Error al cargar promociones:", err);
        });
}

function cargarServicios() {
    fetch("server/database.php?servicios")
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                const select = document.getElementById("servicio");
                select.innerHTML = `<option value="">Selecciona un servicio</option>`;
                data.servicios.forEach((servicio, index) => {
                    const option = document.createElement("option");
                    option.value = index + 1;
                    option.textContent = servicio.nombre;
                    select.appendChild(option);
                });
            } else {
                console.warn("No se pudieron cargar servicios:", data.message);
            }
        })
        .catch(err => {
            console.error("Error al cargar servicios:", err);
        });
}

document.querySelector(".promo-actions button[type='button']").addEventListener("click", guardarPromocion);

function guardarPromocion() {
    const codigo = document.getElementById("codigo").value.trim();
    const descripcion = document.getElementById("descripcion").value.trim();
    const descuento = parseFloat(document.getElementById("descuento").value);
    const servicioId = parseInt(document.getElementById("servicio").value);

    if (!codigo || !descripcion || isNaN(descuento) || isNaN(servicioId)) {
        alert("Todos los campos son obligatorios.");
        return;
    }

    fetch("server/database.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            nuevaPromocion: true,
            codigo,
            descripcion,
            descuento,
            servicio_id: servicioId
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.status === "success") {
            document.getElementById("codigo").value = "";
            document.getElementById("descripcion").value = "";
            document.getElementById("descuento").value = "";
            document.getElementById("servicio").value = "";
            cargarPromociones();
        }
    })
    .catch(err => {
        console.error("Error al guardar la promoción:", err);
        alert("Ocurrió un error.");
    });
}
