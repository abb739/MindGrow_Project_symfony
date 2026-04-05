<?php
namespace App\Repository;

use App\Entity\Seance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SeanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seance::class);
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Recherche DQL par titre ou lieu
    public function findBySearch(string $search): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.titre LIKE :search OR s.lieu LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Séances à venir uniquement
    public function findSeancesAVenir(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.dateDebut > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Tri par capacité (DQL)
    public function findAllOrderedByCapacite(string $order = 'DESC'): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.capacite', $order)
            ->getQuery()
            ->getResult();
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Statistiques - nombre de réservations par séance
    public function findSeancesAvecNbReservations(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
