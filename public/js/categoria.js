const ruta_categoria = ruta_producto_categoria.substring(
  0,
  ruta_producto_categoria.length - 1
);

$(".categorias-btn").on("click", function () {
  console.log(ruta_categoria + $(this).data("categoria"));
  $(".categorias-btn").removeClass("active");
  $(this).addClass("active");
  solicitarProductos($(this).data("categoria"));
});
function solicitarProductos(categoria) {
  $.ajax({
    type: "get",
    url: ruta_categoria + categoria,
    success: function (response) {
      console.log(response);
      mostrarProductos(categoria, response);
    },
  });
}
function mostrarProductos(categoria, productos) {
  console.log("Entrando en categoria");
  $("#categoryTabContent").empty();
  $("#categoryTabContent")
    .append(`<div class="tab-pane fade show active" id="content-${categoria}" role="tabpanel" aria-labelledby="tab-${categoria}">
    <div id="products" class="product-list">
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
        <button class="btn btn-primary add-to-cart" data-id="${element.id}" data-nombre="${element.nombre}" data-precio="${element.precio}" data-imagen="${element.imagen}"">
            Añadir al carrito
        </button>
        `;
    } else {
      productCard += `
        <button class="btn btn-primary notify-me" data-id="${element.id}" data-nombre="${element.nombre}" data-email="${element.email}" data-imagen="${element.imagen}">
            Notificarme cuando haya stock
        </button>
        `;
      // Verificar si el usuario tiene el rol de administrador
      if (element.isAdmin) {
        productCard += `
            <a href="/producto/detalle/${element.id}" class="btn btn-info w-100 mt-2">Ver Detalles</a>
            `;
      }
    }

    // Cerrar la estructura del producto
    productCard += `</div>`;

    // Añadir el producto a la lista
    $("#products").append(productCard);
  });
}