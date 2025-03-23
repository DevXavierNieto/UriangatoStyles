document.addEventListener("DOMContentLoaded", () => {
    cargarPromociones();
    cargarServicios();
});

function cargarPromociones() {
    fetch("server/database.php?listarPromociones=1")
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                const contenedorPromo = document.querySelector(".promo-section-unique");
                contenedorPromo.innerHTML = ""; // Limpiar anteriores

                // Seleccionar 2 promociones al azar
                const promos = data.promociones;
                const seleccionadas = promos.sort(() => 0.5 - Math.random()).slice(0, 2);

                seleccionadas.forEach(promo => {
                    const div = document.createElement("div");
                    div.className = "promo-box-unique";
                    div.textContent = promo.descripcion;

                    // Redirigir al dar clic
                    div.style.cursor = "pointer";
                    div.addEventListener("click", () => {
                        window.location.href = `reservaciones.html?codigo=${encodeURIComponent(promo.codigo)}`;
                    });

                    contenedorPromo.appendChild(div);
                });
            }
        })
        .catch(err => {
            console.error("Error al cargar promociones:", err);
        });
}


function cargarServicios() {
    fetch("server/database.php?servicios=1")
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                const contenedorServicios = document.querySelector(".services-box-unique");
                const servicios = data.servicios;

                // Eliminar los servicios anteriores
                const serviciosAnteriores = contenedorServicios.querySelectorAll(".service-item");
                serviciosAnteriores.forEach(el => el.remove());

                // Insertar los nuevos servicios
                servicios.forEach(s => {
                    const div = document.createElement("div");
                    div.className = "service-item";
                    div.innerHTML = `<span>${s.nombre.toUpperCase()}</span> <span>$${parseFloat(s.costo).toFixed(2)}</span>`;
                    contenedorServicios.insertBefore(div, contenedorServicios.querySelector("a"));
                });
            }
        })
        .catch(err => {
            console.error("Error al cargar servicios:", err);
        });
}
