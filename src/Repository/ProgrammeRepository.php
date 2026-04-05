<?php
namespace App\Repository;

use App\Entity\Programme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProgrammeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Programme::class);
    }

    public function findByFilters(string $search = '', string $catId = ''): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.titre LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($catId) {
            $qb->andWhere('p.idCategorie = :catId')
               ->setParameter('catId', (int)$catId);
        }

        return $qb->getQuery()->getResult();
    }
}