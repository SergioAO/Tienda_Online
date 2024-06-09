<?php

namespace App\Controller;

use App\Entity\Producto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        // Obtener los productos de la primera categoría por defecto
        $productos = $this->entityManager->getRepository(Producto::class)
            ->findBy(['categoria' => $categorias[0]['categoria']]);

        return $this->render('home/home.html.twig', [
            'categorias' => $categorias,
            'productos' => $productos
        ]);
    }

    #[Route('/productos/{categoria}', name: 'productos_por_categoria', methods: ['GET'])]
    public function productosPorCategoria(string $categoria): Response
    {
        // Obtener los productos de la categoría seleccionada
        $productos = $this->entityManager->getRepository(Producto::class)
            ->findBy(['categoria' => $categoria]);

        // Preparar la respuesta JSON con los productos
        $productosArray = [];
        foreach ($productos as $producto) {
            $productosArray[] = [
                'id' => $producto->getId(),
                'nombre' => $producto->getNombre(),
                'imagen' => $producto->getImagen(),
                'precio' => $producto->getPrecio(),
            ];
        }

        // Devolver la respuesta JSON
        return $this->json($productosArray);
    }

    #[Route('/producto/{id}', name: 'producto')]
    public function producto(EntityManagerInterface $em, int $id): Response
    {
        $producto = $em->getRepository(Producto::class)->find($id);
        return $this->render('home/producto.html.twig', [
            'producto' => $producto
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

    #[Route('/productos/marca/{marca}', name: 'productos_por_marca', methods: ['GET'])]
    public function productosPorMarca(string $marca, ProductoRepository $productoRepository): Response
    {
        // Usar el método findByMarca del repositorio para obtener los productos
        $productos = $productoRepository->findByMarca($marca);

        return $this->render('parciales/productos_filtrados.html.twig', [
            'products' => $productos,
            'filterName' => ucfirst($marca), // Capitalizamos la primera letra para la presentación
        ]);
    }

}
