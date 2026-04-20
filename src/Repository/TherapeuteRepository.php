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

    // 🔍 FONCTIONNALITÉ MÉTIER : Recherche DQL par nom, prénom ou spécialité
    public function findBySearch(string $search = '', string $specialite = ''): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($search) {
            $qb->andWhere('t.nom LIKE :search OR t.prenom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($specialite) {
            $qb->andWhere('t.specialite = :specialite')
               ->setParameter('specialite', $specialite);
        }

        return $qb->orderBy('t.nom', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Liste des spécialités distinctes (DQL)
    public function findDistinctSpecialites(): array
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT t.specialite')
            ->where('t.specialite IS NOT NULL')
            ->orderBy('t.specialite', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Thérapeutes triés par note moyenne
    public function findAllOrderedByNom(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
