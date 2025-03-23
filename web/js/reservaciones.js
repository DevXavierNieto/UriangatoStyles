function actualizarCosto() {
    const servicio = document.getElementById("eleccion").value;
    const costo = document.getElementById("costo");
    const selected = serviciosGlobal.find(s => s.nombre === servicio);
    costo.value = selected ? selected.costo : "";
}

function esNombreValido(nombre) {
    return /^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/.test(nombre);
}

function esEmailValido(email) {
    return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email);
}

function esTelefonoValido(telefono) {
    return /^\d{10}$/.test(telefono);
}

function reservar() {
    const nombre = document.getElementById("nombre").value.trim();
    const email = document.getElementById("email").value.trim();
    const telefono = document.getElementById("telefono").value.trim();
    const fecha = document.getElementById("fecha").value;
    const hora = document.getElementById("hora").value;
    const servicio = document.getElementById("eleccion").value;

    if (!nombre || !email || !telefono || !fecha || !hora || !servicio) {
        alert("Todos los campos son obligatorios.");
        return;
    }

    if (!esNombreValido(nombre)) {
        alert("El nombre solo debe contener letras y espacios.");
        return;
    }

    if (!esEmailValido(email)) {
        alert("Correo electrónico inválido.");
        return;
    }

    if (!esTelefonoValido(telefono)) {
        alert("El número de teléfono debe contener exactamente 10 dígitos.");
        return;
    }

    const fechaHora = `${fecha} ${hora}:00`;

    const reservarBtn = document.querySelector(".boton-dorado");
    reservarBtn.disabled = true;

    fetch("server/database.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            nombre,
            email,
            telefono,
            servicio,
            fecha_hora: fechaHora
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert(`Reserva realizada con éxito. Tu código es: ${data.codigo}. Costo: ${data.costo}`);
            document.getElementById("nombre").value = "";
            document.getElementById("email").value = "";
            document.getElementById("telefono").value = "";
            document.getElementById("fecha").value = "";
            document.getElementById("hora").innerHTML = '<option value="">Selecciona una hora</option>';
            document.getElementById("eleccion").value = "";
            document.getElementById("costo").value = "";
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        console.error("Error en la solicitud:", err);
        alert("Ocurrió un error al procesar la reserva.");
    })
    .finally(() => {
        reservarBtn.disabled = false;
    });
}

let serviciosGlobal = [];

document.addEventListener("DOMContentLoaded", () => {
    const fechaInput = document.getElementById("fecha");
    const horaSelect = document.getElementById("hora");

    // Obtener fecha actual en zona horaria de CDMX
    const formatter = new Intl.DateTimeFormat('sv-SE', {
        timeZone: 'America/Mexico_City',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });

    const parts = formatter.formatToParts(new Date());
    const dateParts = {};
    parts.forEach(p => {
        if (p.type !== "literal") dateParts[p.type] = p.value;
    });

    const hoyCDMX = new Date(`${dateParts.year}-${dateParts.month}-${dateParts.day}T00:00:00`);
    hoyCDMX.setDate(hoyCDMX.getDate() + 1); // Mañana

    fechaInput.min = hoyCDMX.toISOString().split("T")[0];

    fechaInput.addEventListener("change", () => {
        const fechaSeleccionada = fechaInput.value;
        if (!fechaSeleccionada) return;

        fetch(`server/database.php?fecha=${fechaSeleccionada}`)
            .then(res => res.json())
            .then(data => {
                if (data.status !== "success") return;

                const ocupadas = data.horasOcupadas;
                const disponibles = generarHorariosDisponibles(ocupadas);

                horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
                disponibles.forEach(hora => {
                    const opt = document.createElement("option");
                    opt.value = hora;
                    opt.textContent = hora;
                    horaSelect.appendChild(opt);
                });
            });
    });

    // Cargar servicios al iniciar
    fetch("server/database.php?servicios=1")
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                serviciosGlobal = data.servicios;
                const select = document.getElementById("eleccion");
                select.innerHTML = '<option value="">Elige un servicio</option>';
                data.servicios.forEach(s => {
                    const opt = document.createElement("option");
                    opt.value = s.nombre;
                    opt.textContent = s.nombre;
                    select.appendChild(opt);
                });
            }
        });
});


function generarHorariosDisponibles(ocupadas) {
    const horarios = [];
    for (let h = 8; h < 20; h++) {
        ["00", "30"].forEach(min => {
            const hora = `${String(h).padStart(2, '0')}:${min}`;
            if (!ocupadas.includes(hora)) {
                horarios.push(hora);
            }
        });
    }
    return horarios;
}

