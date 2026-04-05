<?php
namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Note moyenne d'un thérapeute (DQL)
    public function getNoteMoyenne(int $idTherapeute): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.note) as moyenne')
            ->where('a.idTherapeute = :id')
            ->setParameter('id', $idTherapeute)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float)$result, 1) : 0;
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Avis d'un thérapeute triés par date
    public function findByTherapeute(int $idTherapeute): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.idTherapeute = :id')
            ->setParameter('id', $idTherapeute)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // 🔍 FONCTIONNALITÉ MÉTIER : Nombre d'avis par note (stats)
    public function getDistributionNotes(int $idTherapeute): array
    {
        $results = $this->createQueryBuilder('a')
            ->select('a.note, COUNT(a.id) as nb')
            ->where('a.idTherapeute = :id')
            ->setParameter('id', $idTherapeute)
            ->groupBy('a.note')
            ->orderBy('a.note', 'DESC')
            ->getQuery()
            ->getResult();

        $distribution = [];
        foreach ($results as $r) {
            $distribution[$r['note']] = $r['nb'];
        }
        return $distribution;
    }
}
