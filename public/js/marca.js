const ruta_marca = ruta_producto_marca.substring(
    0, ruta_producto_marca.length -1
);

$(".marcas-btn").on("click", function () {
   console.log(ruta_marca + $(this).data("marca"));
   $(".marcas-btn").removeClass("active");
   $(this).addClass("active");
   solicitarMarcas($(this).data("marca"));
});

function solicitarMarcas(marca) {
   $.ajax({
      type: 'get',
      url: ruta_marca + marca,
      success: function (response) {
         console.log(response);
         mostrarMarcas(marca, response);
      },
   });
}

function mostrarMarcas(marca, productos) {
   console.log($("#marcaTabContent").html());
   $("#marcaTabContent").empty();
   $("#marcaTabContent").append(`<div class="tab-pane fade show active" id="content-${marca}" role="tabpanel" aria-labelledby="tab-${marca}">
    <div id="products-marca" class="product-list">
    </div>
    </div>
    `);

    productos.forEach((element) => {
        // Crear la estructura base del producto
        let productCard = `
    <div class="product-card ${element.stock === 0 ? 'out-of-stock' : ''}">
        <a class="product-link" href="/producto/detalle/${element.id}">
            <img src="${element.imagen}" alt="${element.nombre}">
            <h3>${element.nombre}</h3>
        </a>
        <p class="price">
            <span class="current-price">${element.precio}€</span>
        </p>
        <p class="stock">
            <strong>Stock disponible:</strong> ${element.stock} unidades
        </p>
    `;

        // Añadir el botón correspondiente dependiendo del stock
        if (element.stock > 0) {
            productCard += `
        <button class="btn btn-primary add-to-cart" data-id="${element.id}" data-nombre="${element.nombre}" data-precio="${element.precio}" data-imagen="${element.imagen}">
            Añadir al carrito
        </button>
        `;
        } else {
            productCard += `
        <button class="btn btn-primary notify-me" data-id="${element.id}" data-nombre="${element.nombre}" data-email="${element.email}" data-imagen="${element.imagen}">
            Notificarme cuando haya stock
        </button>
        `;

            // Añadir el botón "Ver Detalles" solo si el usuario es administrador
            if (element.isAdmin) {
                productCard += `
            <a href="/producto/detalle/${element.id}" class="btn btn-info w-100 mt-2">Ver Detalles</a>
            `;
            }
        }

        // Cerrar la estructura del producto
        productCard += `</div>`;

        // Añadir el producto a la lista
        $("#products-marca").append(productCard);
    });
}