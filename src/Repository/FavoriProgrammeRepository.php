<?php
namespace App\Repository;

use App\Entity\FavoriProgramme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriProgrammeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriProgramme::class);
    }

    public function findByUtilisateur(int $userId): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.idUtilisateur = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isFavori(int $userId, int $programmeId): bool
    {
        $result = $this->createQueryBuilder('f')
            ->where('f.idUtilisateur = :uid AND f.idProgramme = :pid')
            ->setParameter('uid', $userId)
            ->setParameter('pid', $programmeId)
            ->getQuery()
            ->getOneOrNullResult();
        return $result !== null;
    }

    public function countByProgramme(int $programmeId): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.idProgramme = :pid')
            ->setParameter('pid', $programmeId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
