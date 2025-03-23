document.addEventListener("DOMContentLoaded", () => {
    fetch("server/database.php?listarPromociones=1")
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                const promoContainer = document.querySelector(".promo-container");
                promoContainer.innerHTML = ""; // Limpiar contenido inicial

                let row;
                data.promociones.forEach((promo, index) => {
                    if (index % 2 === 0) {
                        row = document.createElement("div");
                        row.className = "promo-row";
                        promoContainer.appendChild(row);
                    }

                    const box = document.createElement("div");
                    box.className = "promo-box";
                    box.style.fontFamily = "'Parisienne', cursive";
                    box.style.fontSize = "26px";
                    box.style.cursor = "pointer";
                    box.textContent = `${promo.descripcion} (Código: ${promo.codigo})`;

                    // Redirige al dar clic
                    box.addEventListener("click", () => {
                        window.location.href = `reservaciones.html?codigo=${encodeURIComponent(promo.codigo)}`;
                    });

                    row.appendChild(box);

                    if (index % 2 === 1 || index === data.promociones.length - 1) {
                        const divider = document.createElement("div");
                        divider.className = "promo-divider";
                        promoContainer.appendChild(divider);
                    }
                });
            } else {
                alert("No se pudieron cargar las promociones.");
            }
        })
        .catch(error => {
            console.error("Error al obtener promociones:", error);
        });
});
