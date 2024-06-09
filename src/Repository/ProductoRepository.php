<?php

namespace App\Repository;

use App\Entity\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Producto>
 *
 * @method Producto|null find($id, $lockMode = null, $lockVersion = null)
 * @method Producto|null findOneBy(array $criteria, array $orderBy = null)
 * @method Producto[]    findAll()
 * @method Producto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Producto::class);
    }

    /**
     * @param string $categoria
     * @return Producto[] Returns an array of Producto objects filtered by category
     */
    public function findByCategoria(string $categoria): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.categoria = :categoria')
            ->setParameter('categoria', $categoria)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $marca
     * @return Producto[] Returns an array of Producto objects filtered by brand
     */
    public function findByMarca(string $marca): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.marca = :marca')
            ->setParameter('marca', $marca)
            ->getQuery()
            ->getResult();
    }
}
