<?php

// src/Repository/InteraccionRepository.php

namespace App\Repository;

use App\Entity\Interaccion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Interaccion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Interaccion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Interaccion[]    findAll()
 * @method Interaccion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InteraccionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interaccion::class);
    }

    // Aquí puedes agregar métodos personalizados para manejar consultas específicas.
}
