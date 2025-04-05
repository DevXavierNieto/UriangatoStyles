let serviciosGlobal = [];
let descuentoAplicado = null;

function actualizarCosto() {
    const servicio = document.getElementById("eleccion").value;
    const costo = document.getElementById("costo");
    const selected = serviciosGlobal.find(s => s.nombre === servicio);
    
    if (!selected) {
        costo.value = "";
        return;
    }

    if (descuentoAplicado && descuentoAplicado.servicio === servicio) {
        const nuevoPrecio = selected.costo - (selected.costo * (descuentoAplicado.descuento / 100));
        costo.value = nuevoPrecio.toFixed(2);
    } else {
        costo.value = selected.costo;
    }
}

function getParametroURL(nombre) {
    const params = new URLSearchParams(window.location.search);
    return params.get(nombre);
}

function validarCodigo() {
    const codigo = document.getElementById("codigo-promocion").value.trim();

    if (!codigo) return;

    fetch(`server/database.php?promocion=${codigo}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                descuentoAplicado = data.promocion;

                const select = document.getElementById("eleccion");
                select.value = descuentoAplicado.servicio;

                actualizarCosto();
            } else {
                alert("Código no válido.");
                descuentoAplicado = null;
            }
        })
        .catch(err => {
            console.error("Error al validar código:", err);
            alert("Error al validar el código de promoción.");
        });
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
            fecha_hora: fechaHora,
            promocion: descuentoAplicado?.codigo || null
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert(`Reserva realizada con éxito. Tu código es: ${data.codigo}. Costo: ${data.costo}`);
    
            // Enviar correo de confirmación
            fetch("server/sendEmail.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    email,
                    nombre,
                    fecha: fechaHora,
                    servicio,
                    costo: data.costo,
                    codigo: data.codigo
                })
            })
            .then(res => res.json())
            .then(emailData => {
                console.log("Correo:", emailData.message);
            })
            .catch(err => {
                console.error("Error al enviar correo de confirmación:", err);
            });
    
            // Limpiar formulario
            document.getElementById("nombre").value = "";
            document.getElementById("email").value = "";
            document.getElementById("telefono").value = "";
            document.getElementById("fecha").value = "";
            document.getElementById("hora").innerHTML = '<option value="">Selecciona una hora</option>';
            document.getElementById("eleccion").value = "";
            document.getElementById("codigo-promocion").value = "";
            document.getElementById("costo").value = "";
            descuentoAplicado = null;
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

document.addEventListener("DOMContentLoaded", () => {
    const fechaInput = document.getElementById("fecha");
    const horaSelect = document.getElementById("hora");

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
    hoyCDMX.setDate(hoyCDMX.getDate() + 1);
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

    // Cargar servicios dinámicamente
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

            // Si viene un código de promoción en la URL, aplicarlo automáticamente
            const codigoPromo = getParametroURL("codigo");
            if (codigoPromo) {
                document.getElementById("codigo-promocion").value = codigoPromo;
                validarCodigo();
            }
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
