<?php

namespace App\Controller;

use App\Entity\Compra;
use App\Entity\DatoDePago;
use App\Entity\Pedido;
use App\Entity\Pregunta;
use App\Entity\Producto;
use App\Form\DatoPagoFormType;
use App\Form\PreguntaFormType;
use App\Repository\ProductoRepository;
use App\Repository\UsuarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Form\DireccionFormType;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class HomeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ProductoRepository $repo_producto;
    private UsuarioRepository $repo_usuario;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, ProductoRepository $repo_producto, UsuarioRepository $repo_usuario, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->repo_producto = $repo_producto;
        $this->repo_usuario = $repo_usuario;
        $this->serializer = $serializer;
    }
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Obtener todas las categorías únicas de los productos
        $categorias = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT p.categoria')
            ->from(Producto::class, 'p')
            ->getQuery()
            ->getResult();

        $marcas = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT p.marca')
            ->from(Producto::class, 'p')
            ->getQuery()
            ->getResult();


        $productos = [];
        $productos_marca = [];

        if (count($categorias) > 0) {
            // Obtener los productos de la primera categoría por defecto
            $productos = $this->entityManager->getRepository(Producto::class)
                ->findBy(['categoria' => $categorias[0]['categoria']]);
        }

        if (count($marcas) > 0) {
            // Obtener los productos de la primera marca por defecto
            $productos_marca = $this->entityManager->getRepository(Producto::class)
                ->findBy(['marca' => $marcas[0]['marca']]);
        }

        return $this->render('home/home.html.twig', [
            'categorias' => $categorias,
            'productos' => $productos,
            'marcas' => $marcas,
            'productos_marca' => $productos_marca
        ]);
    }

    #[Route('/productos/{categoria}', name: 'productos_por_categoria', methods: ['GET'])]
    public function productosPorCategoria(string $categoria): Response
    {
        // Obtener los productos de la categoría seleccionada
        $productos = $this->entityManager->getRepository(Producto::class)
            ->findBy(['categoria' => $categoria]);

        // Determinar si el usuario actual es administrador
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Preparar la respuesta JSON con los productos
        $productosArray = [];
        foreach ($productos as $producto) {
            $productosArray[] = [
                'id' => $producto->getId(),
                'nombre' => $producto->getNombre(),
                'imagen' => $producto->getImagen(),
                'stock' => $producto->getStock(),
                'precio' => $producto->getPrecio(),
                'isAdmin' => $isAdmin,
            ];
        }

        // Devolver la respuesta JSON
        return $this->json($productosArray);
    }

    #[Route('/producto/{marca}', name: 'productos_por_marca', methods: ['GET'])]
    public function productosPorMarca(string $marca): Response
    {
        // Obtener los productos de la categoría seleccionada
        $productos = $this->entityManager->getRepository(Producto::class)
            ->findBy(['marca' => $marca]);

        // Determinar si el usuario actual es administrador
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Preparar la respuesta JSON con los productos
        $productosArray = [];
        foreach ($productos as $producto) {
            $productosArray[] = [
                'id' => $producto->getId(),
                'nombre' => $producto->getNombre(),
                'imagen' => $producto->getImagen(),
                'stock' => $producto->getStock(),
                'precio' => $producto->getPrecio(),
                'isAdmin' => $isAdmin,
            ];
        }

        // Devolver la respuesta JSON
        return $this->json($productosArray);
    }

    #[Route('/producto/detalle/{id}', name: 'producto_detalle', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function producto(EntityManagerInterface $em, Request $request, int $id): Response
    {
        // Obtener el producto
        $producto = $em->getRepository(Producto::class)->find($id);

        if (!$producto) {
            throw $this->createNotFoundException('El producto no existe');
        }

        // Obtener los comentarios (preguntas) relacionados con el producto
        $preguntas = $em->getRepository(Pregunta::class)->findBy(['producto' => $producto], ['fecha' => 'DESC']);

        // Crear el formulario para una nueva pregunta
        $pregunta = new Pregunta();
        $form = $this->createForm(PreguntaFormType::class, $pregunta);
        $form->handleRequest($request);

        // Procesar el formulario si es enviado
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser()) {
                $pregunta->setProducto($producto);
                $pregunta->setUsuario($this->getUser());
                $pregunta->setFecha(new \DateTime());

                $em->persist($pregunta);
                $em->flush();

                // Redirigir para evitar reenvío del formulario al recargar
                return $this->redirectToRoute('producto_detalle', ['id' => $producto->getId()]);
            } else {
                // Mensaje de error si el usuario no está autenticado
                $this->addFlash('warning', 'Debes estar registrado para publicar comentarios.');
            }
        }

        return $this->render('home/producto.html.twig', [
            'producto' => $producto,
            'preguntas' => $preguntas,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/categorias', name: 'categorias', methods: ['GET'])]
    public function categorias(): Response
    {
        $categorias = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT p.categoria')
            ->from(Producto::class, 'p')
            ->getQuery()
            ->getResult();
        // Devolver la respuesta JSON
        return $this->json($categorias);
    }
    #[Route('/marcas', name: 'marcas', methods: ['GET'])]
    public function marcas(): Response
    {
        $marcas = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT p.marca')
            ->from(Producto::class, 'p')
            ->getQuery()
            ->getResult();
        // Devolver la respuesta JSON
        return $this->json($marcas);
    }

    #[Route('/carrito', name: 'carrito', methods: ['GET'])]
    public function carrito(SessionInterface $session): Response
    {
        if (!$session->has('carrito')) {
            $session->set('carrito', []);
        }

        return $this->json($session->get('carrito'));
    }

    #[Route('/carrito_agregar', name: 'carrito_agregar', methods: ['POST'])]
    public function carrito_agregar(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        if (!$session->has('carrito')) {
            $session->set('carrito', []);
        }

        $id = $request->request->get('id');
        $nombre = $request->request->get('nombre');
        $precio = $request->request->get('precio');
        $imagen = $request->request->get('imagen');

        // Obtener el producto de la base de datos para verificar el stock
        $producto = $entityManager->getRepository(Producto::class)->find($id);

        if (!$producto) {
            return new JsonResponse(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $stockDisponible = $producto->getStock();

        // Obtener el carrito actual
        $carrito = $session->get('carrito');
        $productoEncontrado = false;

        // Verificar si el producto ya está en el carrito
        foreach ($carrito as &$productoEnCarrito) {
            if ($productoEnCarrito['id'] == $id) {
                $productoEncontrado = true;
                // Si no existe la propiedad 'cantidad', inicializarla
                if (!isset($productoEnCarrito['cantidad'])) {
                    $productoEnCarrito['cantidad'] = 0;
                }

                // Incrementar la cantidad pero no exceder el stock disponible
                if ($productoEnCarrito['cantidad'] < $stockDisponible) {
                    $productoEnCarrito['cantidad']++;
                    $productoEnCarrito['precio_total'] = $precio * $productoEnCarrito['cantidad'];
                } else {
                    $productoEnCarrito['cantidad'] = $stockDisponible; // Ajustar a la cantidad máxima disponible en stock
                }
                break;
            }
        }

        // Si el producto no está en el carrito, añadirlo con la propiedad 'cantidad'
        if (!$productoEncontrado) {
            $carrito[] = [
                'id' => $id,
                'imagen' => $imagen,
                'nombre' => $nombre,
                'precio' => $precio,
                'cantidad' => min(1, $stockDisponible), // Inicializar la cantidad pero no exceder el stock disponible
                'precio_total' => $precio
            ];
        }

        // Guardar el carrito actualizado en la sesión
        $session->set('carrito', $carrito);

        // Devolver la respuesta con el carrito y la cantidad actualizada
        return $this->json([
            'carrito' => $carrito,
            'stock' => $stockDisponible
        ]);
    }

    #[Route('/carrito_eliminar', name: 'carrito_eliminar', methods: ['POST'])]
    public function carrito_eliminar(Request $request, SessionInterface $session): Response
    {
        $id = $request->request->get('id');
        $carrito = $session->get('carrito');
        $precioTotal = 0;

        for ($i = 0; $i < count($carrito); $i++) {
            if ($carrito[$i]['id'] == $id) {
                if ($carrito[$i]['cantidad'] > 1) {
                    $carrito[$i]['cantidad']--;
                    $carrito[$i]['precio'] = $carrito[$i]['precio'] / ($carrito[$i]['cantidad'] + 1) * $carrito[$i]['cantidad'];
                } else {
                    array_splice($carrito, $i, 1);
                }
                break;
            }
        }

        foreach ($carrito as $producto) {
            $precioTotal += $producto['precio'];
        }

        $session->set('carrito', $carrito);
        return $this->json([
            'carrito' => $carrito,
            'precioTotal' => $precioTotal
        ]);
    }

    #[Route('/ver_carrito', name: 'ver_carrito', methods: ['GET'])]
    public function verCarrito(SessionInterface $session): Response
    {
        // Obtener los productos del carrito desde la sesión
        $carrito = $session->get('carrito', []);

        // Calcular el precio total del carrito
        $precioTotal = array_reduce($carrito, function($total, $producto) {
            return $total + ($producto['precio'] * $producto['cantidad']);
        }, 0);

        // Renderizar la vista del carrito
        return $this->render('carrito/ver_carrito.html.twig', [
            'carrito' => $carrito,
            'precioTotal' => $precioTotal
        ]);
    }


    #[Route('/productos/categoria/{categoria}', name: 'productos_categoria', methods: ['GET'])]
    public function productosCategoria(string $categoria, ProductoRepository $productoRepository): Response
    {
        // Usar el método findByCategoria del repositorio para obtener los productos
        $productos = $productoRepository->findByCategoria($categoria);

        return $this->render('parciales/productos_filtrados.html.twig', [
            'products' => $productos,
            'filterName' => ucfirst($categoria), // Capitalizamos la primera letra para la presentación
        ]);
    }

    #[Route('/productos/marca/{marca}', name: 'productos_marca', methods: ['GET'])]
    public function productosMarca(string $marca, ProductoRepository $productoRepository): Response
    {
        // Usar el método findByMarca del repositorio para obtener los productos
        $productos = $productoRepository->findByMarca($marca);

        return $this->render('parciales/productos_filtrados.html.twig', [
            'products' => $productos,
            'filterName' => ucfirst($marca), // Capitalizamos la primera letra para la presentación
        ]);
    }
    #[Route('/administracion', name: 'administracion')]
    public function administracion(): Response
    {
        // Obtener todas las categorías únicas de los productos
        $categorias = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT p.categoria')
            ->from(Producto::class, 'p')
            ->getQuery()
            ->getResult();

        $marcas = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT p.marca')
            ->from(Producto::class, 'p')
            ->getQuery()
            ->getResult();

        return $this->render('perfil/administracion.html.twig', [
            'categorias' => $categorias,
            'marcas' => $marcas
        ]);
    }
    #[Route('/nuevoProducto', name: 'nuevoProducto')]
    public function nuevoProducto(): Response
    {
        $producto = new Producto();
        $producto->setNombre($_POST['nombre']);
        $producto->setDescripcion($_POST['descripcion']);
        $producto->setPrecio($_POST['precio']);
        //$producto->setDescuento($_POST['descuento']);
        $producto->setCategoria($_POST['categoria']);
        $producto->setMarca($_POST['marca']);
        $producto->setColor($_POST['color']);
        $producto->setImagen("/uploads/productos/" . $_FILES['foto']['name']);
        $producto->setStock($_POST['stock']);
        //$producto->setEstado($_POST['estado']);

        $this->entityManager->persist($producto);
        $this->entityManager->flush();
        if (isset($_FILES['foto']) && strlen($_FILES['foto']['name'])) {
            $this->guardarArchivo($_FILES['foto']);
        }
        return $this->redirectToRoute('home');
    }

    private function guardarArchivo($file)
    {
        $filesystem = new Filesystem();
        $folderPath = $this->getParameter('kernel.project_dir') . '/public/uploads/productos/';
        if (!$filesystem->exists($folderPath)) {
            $filesystem->mkdir($folderPath, 0777, true);
        }
        $filePath = $folderPath . $file['name'];
        move_uploaded_file($file['tmp_name'], $filePath);
    }

    #[Route('/buscador', name: 'buscador')]
    public function buscador(): Response
    {
        $parametro = strtolower($_POST['titulo']);

        $productos = $this->buscarProducto($parametro);

        return $this->render('home/producto_buscado.html.twig', [
            'productos' => $productos
        ]);
    }


    private function buscarUsuario($parametro)
    {
        return $this->repo_usuario->createQueryBuilder('u')
            ->andWhere("LOWER(u.email) LIKE '%" . $parametro . "%'")
            ->getQuery()->getResult();
    }

    #[Route('/buscarProducto', name: 'buscarProducto', methods: ['POST'])]
    public function buscarProductos(Request $request): Response
    {
        $producto = $request->request->get('producto');

        if ($producto === null) {
            return $this->json(['message' => 'Parámetro de búsqueda faltante'], Response::HTTP_BAD_REQUEST);
        }

        $producto = strtolower($producto);

        $lista = $this->buscarProducto($producto);

        if (!$lista) {
            return $this->json(['message' => 'No se encontraron productos.'], Response::HTTP_NOT_FOUND);
        }

        // Configurar el serializador para manejar referencias circulares
        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
        ];

        // Serializar la respuesta con el contexto configurado
        $jsonContent = $this->serializer->serialize($lista, 'json', $context);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    private function buscarProducto($parametro)
    {
        return $this->repo_producto->createQueryBuilder('p')
            ->andWhere("LOWER(p.nombre) LIKE '" . $parametro . "%'")
            ->orWhere("LOWER(p.categoria) LIKE '" . $parametro . "%'")
            ->orWhere("LOWER(p.marca) LIKE '" . $parametro . "%'")
            ->getQuery()->getResult();
    }

    #[Route('/buscarUsuario', name: 'buscarUsuario', methods: ['POST'])]
    public function buscarUsuarios(Request $request): Response
    {
        $usuario = strtolower($request->request->get('usuario'));
        $lista = $this->buscarUsuario($usuario);
        return $this->json($lista);
    }

    #[Route('/eliminarUsuario', name: 'eliminarUsuario', methods: ['POST'])]
    public function eliminarUsuario(Request $request): Response
    {
        $id = $request->request->get('id');
        $user = $this->repo_usuario->find($id);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        return $this->json($user);
    }

    #[Route('/eliminarProducto', name: 'eliminarProducto', methods: ['POST'])]
    public function eliminarProducto(Request $request, SessionInterface $session): Response
    {
        $id = $request->request->get('id');
        $product = $this->repo_producto->find($id);
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        return $this->json($product);
    }

    #[Route('/ingresar_direccion', name: 'ingresar_direccion')]
    public function mostrarFormularioDireccion(): Response
    {
        $form = $this->createForm(DireccionFormType::class);
        return $this->render('compra/ingresar_direccion.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/guardar_direccion', name: 'guardar_direccion', methods: ['POST'])]
    public function guardarDireccion(Request $request): Response
    {
        // Obtener los datos enviados en formato JSON
        $data = json_decode($request->getContent(), true);

        // Verificar que los datos son válidos
        if ($data === null || !isset($data['fullData'])) {
            return new JsonResponse(['success' => false, 'errors' => ['Invalid data received.']], Response::HTTP_BAD_REQUEST);
        }

        // Extraer la cadena de texto completa
        $direccionCompleta = $data['fullData'];

        // Obtener el usuario actual
        $userInterface = $this->getUser();
        $usuario = $this->repo_usuario->findOneByEmail($userInterface->getUserIdentifier());

        if ($usuario) {
            // Buscar un DatoDePago existente para el usuario
            $datoDePago = $this->entityManager->getRepository(DatoDePago::class)->findOneBy(['usuario' => $usuario]);

            if ($datoDePago) {
                // Si existe, actualizar la dirección de facturación
                $datoDePago->setDireccionFacturacion($direccionCompleta);
            } else {
                // Si no existe, crear un nuevo DatoDePago
                $datoDePago = new DatoDePago();
                $datoDePago->setDireccionFacturacion($direccionCompleta);
                $datoDePago->setUsuario($usuario);
                $this->entityManager->persist($datoDePago);
            }

            // Guardar los cambios en la base de datos
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'redirect_url' => $this->generateUrl('ingresar_tarjeta')]);
        } else {
            return new JsonResponse(['success' => false, 'errors' => ['User not authenticated.']], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/provincias_por_comunidad/{comunidad}', name: 'provincias_por_comunidad', methods: ['GET'])]
    public function provinciasPorComunidad(Request $request, $comunidad): JsonResponse
    {
        $provinciasPorComunidad = $this->getProvinciasPorComunidad();

        $provincias = $provinciasPorComunidad[$comunidad] ?? [];

        return $this->json($provincias);
    }

    private function getProvinciasPorComunidad(): array
    {
        return [
            'Andalucía' => [
                'Almería', 'Cádiz', 'Córdoba', 'Granada', 'Huelva', 'Jaén', 'Málaga', 'Sevilla'
            ],
            'Aragón' => [
                'Huesca', 'Teruel', 'Zaragoza'
            ],
            'Asturias' => [
                'Asturias'
            ],
            'Islas Baleares' => [
                'Islas Baleares'
            ],
            'Canarias' => [
                'Las Palmas', 'Santa Cruz de Tenerife'
            ],
            'Cantabria' => [
                'Cantabria'
            ],
            'Castilla-La Mancha' => [
                'Albacete', 'Ciudad Real', 'Cuenca', 'Guadalajara', 'Toledo'
            ],
            'Castilla y León' => [
                'Ávila', 'Burgos', 'León', 'Palencia', 'Salamanca', 'Segovia', 'Soria', 'Valladolid', 'Zamora'
            ],
            'Cataluña' => [
                'Barcelona', 'Girona', 'Lleida', 'Tarragona'
            ],
            'Extremadura' => [
                'Badajoz', 'Cáceres'
            ],
            'Galicia' => [
                'A Coruña', 'Lugo', 'Ourense', 'Pontevedra'
            ],
            'Madrid' => [
                'Madrid'
            ],
            'Murcia' => [
                'Murcia'
            ],
            'Navarra' => [
                'Navarra'
            ],
            'País Vasco' => [
                'Álava', 'Gipuzkoa', 'Bizkaia'
            ],
            'La Rioja' => [
                'La Rioja'
            ],
            'Comunidad Valenciana' => [
                'Alicante', 'Castellón', 'Valencia'
            ],
            'Ceuta' => [
                'Ceuta'
            ],
            'Melilla' => [
                'Melilla'
            ]
        ];
    }

    #[Route('/ingresar_tarjeta', name: 'ingresar_tarjeta')]
    public function mostrarFormularioTarjeta(): Response
    {
        $form = $this->createForm(DatoPagoFormType::class);
        return $this->render('compra/ingresar_tarjeta.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/guardar_datos_tarjeta', name: 'guardar_datos_tarjeta', methods: ['POST'])]
    public function guardarTarjeta(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Obtener el usuario actual
        $userInterface = $this->getUser();
        $usuario = $this->repo_usuario->findOneByEmail($userInterface->getUserIdentifier());

        // Buscar si ya existe un registro de DatoDePago para el usuario actual
        $datoDePago = $entityManager->getRepository(DatoDePago::class)->findOneBy(['usuario' => $usuario]);

        if (!$datoDePago) {
            // Si no existe, crear un nuevo DatoDePago
            $datoDePago = new DatoDePago();
            $datoDePago->setUsuario($usuario);
        }

        // Crear y manejar el formulario
        $form = $this->createForm(DatoPagoFormType::class, $datoDePago);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Actualizar o establecer los datos de pago con los nuevos valores del formulario
            $entityManager->persist($datoDePago);
            $entityManager->flush();

            // Añadir un mensaje de éxito y redirigir a la página principal
            $this->addFlash('success', 'Datos de pago guardados con éxito.');
            return $this->redirectToRoute('confirmacion_compra');
        }

        // Si el formulario no es válido, renderizar el formulario con errores
        return $this->render('compra/ingresar_tarjeta.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/confirmacion_compra', name: 'confirmacion_compra')]
    public function confirmacionCompra(SessionInterface $session, EntityManagerInterface $em): Response
    {
        // Obtener los productos del carrito desde la sesión
        $carrito = $session->get('carrito', []);

        if (empty($carrito)) {
            $this->addFlash('warning', 'No hay productos en tu carrito.');
            return $this->redirectToRoute('home');
        }

        // Obtener el usuario logueado
        $usuario = $this->getUser();
        if (!$usuario) {
            $this->addFlash('warning', 'Debes iniciar sesión para confirmar tu compra.');
            return $this->redirectToRoute('login');
        }

        // Obtener la dirección del usuario
        $datoDePago = $em->getRepository(DatoDePago::class)->findOneBy(['usuario' => $usuario]);
        if (!$datoDePago) {
            $this->addFlash('warning', 'No se pudo encontrar la dirección de envío.');
            return $this->redirectToRoute('home');
        }

        $direccion = $datoDePago->getDireccionFacturacion();
        $fecha = new \DateTime();

        // Crear una nueva instancia de Pedido
        $pedido = new Pedido();
        $pedido->setIdUsuario($usuario);
        $pedido->setDireccion($direccion);
        $pedido->setFecha($fecha);

        $em->persist($pedido); // Persistir el Pedido primero para obtener el ID

        // Guardar los productos del carrito como Compras
        foreach ($carrito as $productoData) {
            $producto = $em->getRepository(Producto::class)->find($productoData['id']);
            if (!$producto) {
                $this->addFlash('warning', 'No se pudo encontrar el producto con ID: ' . $productoData['id']);
                return $this->redirectToRoute('carrito');
            }

            // Verificar el stock
            $cantidad = $productoData['cantidad'];
            if ($producto->getStock() < $cantidad) {
                $this->addFlash('warning', 'No hay suficiente stock para el producto: ' . $producto->getNombre());
                return $this->redirectToRoute('carrito');
            }

            // Reducir el stock del producto
            $producto->setStock($producto->getStock() - $cantidad);

            // Crear una nueva instancia de Compra
            $compra = new Compra();
            $compra->setIdPedido($pedido);
            $compra->setIdProducto($producto);
            $compra->setUnidades($cantidad);
            $precioCompra = floatval($productoData['precio']) * floatval($cantidad);
            $compra->setPrecio_compra((string) $precioCompra);

            $pedido->addCompra($compra);

            // Persistir la compra y el producto
            $em->persist($compra);
            $em->persist($producto);
        }

        // Guardar los cambios en la base de datos
        $em->flush();

        // Limpiar el carrito en la sesión
        $session->remove('carrito');

        // Obtener todas las compras asociadas al pedido
        $compras = $pedido->getCompras();

        // Convertir compras a un array para pasar a la vista
        $comprasArray = [];
        foreach ($compras as $compra) {
            $comprasArray[] = [
                'idProducto' => $compra->getIdProducto()->getId(),
                'nombreProducto' => $compra->getIdProducto()->getNombre(),
                'unidades' => $compra->getUnidades(),
                'precio_compra' => floatval($compra->getPrecio_compra()),
            ];
        }

        // Renderizar la vista de confirmación de compra
        return $this->render('compra/confirmacion_compra.html.twig', [
            'compras' => $comprasArray,
            'pedido' => $pedido,
        ]);
    }

    #[Route('/carrito/vaciar', name: 'vaciar_carrito', methods: ['POST'])]
    public function vaciarCarrito(SessionInterface $session): JsonResponse
    {
        // Elimina el carrito de la sesión
        $session->remove('carrito');

        // Devuelve una respuesta JSON para indicar que la operación fue exitosa
        return new JsonResponse(['success' => true]);
    }
}
