const ruta_productos_categoria = ruta_producto_category.substring(
    0,
    ruta_producto_category.length - 1
);
const ruta_productos_marca = ruta_producto_mark.substring(
    0,
    ruta_producto_mark.length - 1
);

function toggleSidebar(element) {
  let sidebar = document.getElementById(element);
  if (sidebar.style.width === "250px") {
    sidebar.style.width = "0";
  } else {
    sidebar.style.width = "250px";
  }
}
function categorias() {
  $.ajax({
    type: "get",
    url: ruta_categorias,
    success: function (response) {
      console.log(response);

      response.forEach((element) => {
        $(".categorias").append(
          `<li><a href="${ruta_productos_categoria + element.categoria}">${element.categoria}</a></li>`
        );
      });
    },
  });
}
function marcas() {
  $.ajax({
    type: "get",
    url: ruta_marcas,
    success: function (response) {
      console.log(response);
      response.forEach((element) => {
        if (element.marca != null) {
          $(".marcas").append(`<li><a href="${ruta_productos_marca + element.marca}">${element.marca}</a></li>`);
        }
      });
    },
  });
}
$(".full-categorias").on("click", function () {
  toggleSidebar("miCategoria");
});
function pedirCarrito() {
  $.ajax({
    type: "get",
    url: ruta_carrito,
    success: function (response) {
      console.log(response);
      $(".carrito-compra").empty();
      let totalCantidad = 0;
      let totalPrecio = 0;
      response.forEach((element) => {
        console.log('id: ' + element.id);
        console.log('Cantidad: ' + element.cantidad);
        totalCantidad += element.cantidad;
        totalPrecio += element.precio * element.cantidad;
        $(".carrito-compra").append(
            `<li class="list-group-item">
                <div class="producto-en-carrito d-flex align-items-center" data-id="${element.id}">
                    <img src="${element.imagen}" class="img-thumbnail me-3" style="width: 50px; height: 50px;" alt="${element.nombre}">
                    <div class="flex-grow-1">
                        <strong>${element.nombre}</strong> - 
                        <span class="precio-producto">${(element.precio * element.cantidad).toFixed(2)}€</span>
                    </div>
                    <button data-id="${element.id}" class="btn btn-danger btn-sm eliminar-producto-carrito me-2">
                        <i class="bi bi-trash"></i>
                    </button>
                    <span class="cantidad-producto badge bg-primary rounded-pill">${element.cantidad}</span>
                </div>
            </li>`
        );
      });
      $(".cart-badge").html(totalCantidad);
      // Opcional: si tienes un lugar para mostrar el precio total, puedes actualizarlo aquí
      $(".cart-total").html(totalPrecio);
    },
  });
}

$(".mi-carrito").on("click", function () {
  pedirCarrito();
  toggleSidebar("miCarrito");
});
categorias();
marcas();
pedirCarrito();
