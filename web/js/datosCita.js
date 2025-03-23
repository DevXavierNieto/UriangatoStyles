let codigoActual = null;

function buscarCita() { 
    const codigo = document.getElementById("codigoCita").value.trim().toUpperCase();
    const regex = /^[A-Z0-9]{5}$/;

    if (!regex.test(codigo)) {
        alert("Por favor, ingresa un código válido de 5 caracteres alfanuméricos.");
        return;
    }

    fetch("server/database.php?codigo=" + encodeURIComponent(codigo))
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                document.getElementById("fecha").innerText = data.fecha;
                document.getElementById("hora").innerText = data.hora;
                document.getElementById("cliente").innerText = data.cliente;
                document.getElementById("ubicacion").innerText = data.ubicacion;
                document.getElementById("servicio").innerText = data.servicio;
                document.getElementById("costo").innerText = data.costo;
                document.getElementById("cancelarBtn").style.display = "inline-block";
                codigoActual = codigo;
                alert("Cita encontrada con éxito.");
            } else {
                alert(data.message);
                limpiarCampos();
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Hubo un problema al buscar la cita.");
            limpiarCampos();
        });
}

function cancelarCita() {
    if (!codigoActual) return;

    if (!confirm("¿Estás seguro de que deseas cancelar esta cita? Esta acción no se puede deshacer.")) return;

    fetch("server/database.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ codigo: codigoActual })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert("Cita cancelada correctamente.");
            limpiarCampos();
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Ocurrió un error al cancelar la cita.");
    });
}

function limpiarCampos() {
    document.getElementById("fecha").innerText = "";
    document.getElementById("hora").innerText = "";
    document.getElementById("cliente").innerText = "";
    document.getElementById("ubicacion").innerText = "";
    document.getElementById("servicio").innerText = "";
    document.getElementById("costo").innerText = "";
    document.getElementById("cancelarBtn").style.display = "none";
    document.getElementById("codigoCita").innerText = "";
    codigoActual = null;
}
