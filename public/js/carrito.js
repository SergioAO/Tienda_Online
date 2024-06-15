$(document).on("click", ".add-to-cart", function() {
  let id = $(this).data("id");
  let nombre = $(this).data("nombre");
  let precio = $(this).data("precio");
  let imagen = $(this).data("imagen");

  $.ajax({
    type: "post",
    url: ruta_carrito_agregar,
    data: {
      id: id,
      nombre: nombre,
      precio: precio,
      imagen: imagen
    },
    dataType: "json",
    success: function(response) {
      // Buscar si el producto ya está en el carrito
      let productoExistente = $(".producto-en-carrito[data-id='" + id + "']");
      let stockDisponible = response.stock;
      let cantidadActual;

      if (productoExistente.length) {
        // Producto ya existe, actualizar cantidad y precio
        cantidadActual = parseInt(productoExistente.find('.cantidad-producto').text());
        let nuevaCantidad = cantidadActual + 1;

        if (nuevaCantidad > stockDisponible) {
          nuevaCantidad = stockDisponible; // Ajustar a la cantidad máxima disponible en stock
        }

        productoExistente.find('.cantidad-producto').text(nuevaCantidad);
        productoExistente.find('.precio-producto').text((precio * nuevaCantidad).toFixed(2) + "€");
      } else {
        // Producto no existe, añadir nuevo elemento al carrito
        $(".carrito-compra").append(
            `<li class="list-group-item">
                        <div class="producto-en-carrito d-flex align-items-center" data-id="${id}">
                            <img src="${imagen}" class="img-thumbnail me-3" style="width: 50px; height: 50px;" alt="${nombre}">
                            <div class="flex-grow-1">
                                <strong>${nombre}</strong> - 
                                <span class="precio-producto">${precio}€</span>
                            </div>
                            <button data-id="${id}" class="btn btn-danger btn-sm eliminar-producto-carrito me-2">
                                <i class="bi bi-trash"></i>
                            </button>
                            <span class="cantidad-producto badge bg-primary rounded-pill">1</span>
                        </div>
                    </li>`
        );
      }

      // Calcular la cantidad total de todos los productos en el carrito
      let cantidadTotal = 0;
      $(".producto-en-carrito .cantidad-producto").each(function() {
        cantidadTotal += parseInt($(this).text());
      });

      // Actualizar la insignia de cantidad en el carrito
      $(".cart-badge").html(cantidadTotal);
    }
  });
});

$(document).on("click", ".eliminar-producto-carrito", function() {
  let id = $(this).data("id");
  let elementoProducto = $(this).closest('.producto-en-carrito');
  let cantidadActual = parseInt(elementoProducto.find('.cantidad-producto').text());
  let precioUnitario = parseFloat(elementoProducto.find('.precio-producto').text().replace('€', '')) / cantidadActual;

  $.ajax({
    type: "post",
    url: ruta_carrito_eliminar,
    data: {
      id: id
    },
    dataType: "json",
    success: function(response) {
      if (cantidadActual > 1) {
        // Reducir la cantidad en el DOM
        elementoProducto.find('.cantidad-producto').text(cantidadActual - 1);
        elementoProducto.find('.precio-producto').text((precioUnitario * (cantidadActual - 1)).toFixed(2) + "€");
      } else {
        // Eliminar el elemento del DOM si la cantidad es 1
        elementoProducto.remove();
      }

      // Calcular la cantidad total de todos los productos en el carrito
      let cantidadTotal = 0;
      $(".producto-en-carrito .cantidad-producto").each(function() {
        cantidadTotal += parseInt($(this).text());
      });

      // Actualizar la insignia de cantidad en el carrito
      $(".cart-badge").html(cantidadTotal);

      // Actualizar el precio total del carrito
      $(".cart-total").html(response.precioTotal.toFixed(2));

      console.log(response);
    }
  });
});

$(document).ready(function() {
  $('#vaciar-carrito').on('click', function() {
    if (confirm('¿Estás seguro de que quieres vaciar tu carrito?')) {
      $.ajax({
        url: ruta_borrar_sesion_carrito,
        type: 'POST',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success) {
            // Recargar la página para reflejar el carrito vacío
            location.reload();
          } else {
            alert('Hubo un problema al vaciar el carrito.');
          }
        },
        error: function(error) {
          console.error('Error:', error);
        }
      });
    }
  });
});