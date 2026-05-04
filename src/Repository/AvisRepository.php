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

    // 📊 STATS : Distribution globale des notes (toutes plateformes)
    public function getGlobalDistribution(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.note, COUNT(a.id) as nb')
            ->groupBy('a.note')
            ->orderBy('a.note', 'ASC')
            ->getQuery()->getResult();

        $dist = array_fill(1, 5, 0);
        foreach ($rows as $r) {
            if ($r['note'] >= 1 && $r['note'] <= 5) $dist[(int)$r['note']] = (int)$r['nb'];
        }
        return $dist;
    }

    // 📊 STATS : Top N thérapeutes par note moyenne
    public function getTopByMoyenne(int $limit = 8): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.idTherapeute, AVG(a.note) as moyenne, COUNT(a.id) as nbAvis')
            ->groupBy('a.idTherapeute')
            ->having('COUNT(a.id) >= 1')
            ->orderBy('moyenne', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    // 📊 STATS : Top N thérapeutes par nombre d'avis
    public function getTopByNbAvis(int $limit = 8): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.idTherapeute, COUNT(a.id) as nbAvis, AVG(a.note) as moyenne')
            ->groupBy('a.idTherapeute')
            ->orderBy('nbAvis', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    // 📊 STATS : Avis par mois sur les 12 derniers mois
    public function getReviewsPerMonth(): array
    {
        $conn  = $this->getEntityManager()->getConnection();
        $since = (new \DateTime('-12 months'))->format('Y-m-d');
        $sql   = "SELECT DATE_FORMAT(date_avis, '%Y-%m') AS mois, COUNT(*) AS nb
                  FROM avis
                  WHERE date_avis >= :since
                  GROUP BY mois
                  ORDER BY mois ASC";
        $rows = $conn->executeQuery($sql, ['since' => $since])->fetchAllAssociative();
        return $rows;
    }
}
