<?php

namespace App\Repository;

use App\Entity\Therapeute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TherapeuteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Therapeute::class);
    }

    public function findAllSorted(string $sort = 'desc'): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.dateInscription', strtoupper($sort))
            ->getQuery()->getResult();
    }

    public function search(string $query, string $sort = 'desc'): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.nom LIKE :q OR t.prenom LIKE :q OR t.specialite LIKE :q OR t.email LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('t.dateInscription', strtoupper($sort))
            ->getQuery()->getResult();
    }

    public function findFiltered(string $search = '', string $specialite = ''): array
    {
        $qb = $this->createQueryBuilder('t');
        if ($search) {
            $qb->andWhere('t.nom LIKE :s OR t.prenom LIKE :s OR t.specialite LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }
        if ($specialite) {
            $qb->andWhere('t.specialite = :spec')
               ->setParameter('spec', $specialite);
        }
        return $qb->getQuery()->getResult();
    }

    public function findAllSpecialites(): array
    {
        return array_column(
            $this->createQueryBuilder('t')
                ->select('DISTINCT t.specialite')
                ->where('t.specialite IS NOT NULL')
                ->getQuery()->getArrayResult(),
            'specialite'
        );
    }
}