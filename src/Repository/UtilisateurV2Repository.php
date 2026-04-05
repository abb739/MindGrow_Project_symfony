<?php

namespace App\Repository;

use App\Entity\UtilisateurV2;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UtilisateurV2>
 */
class UtilisateurV2Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtilisateurV2::class);
    }

    /**
     * ✅ CONTRÔLE DE SAISIE - Check if email already exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findOneBy(['email' => trim($email)]) !== null;
    }

    /**
     * ✅ CONTRÔLE DE SAISIE - Find user by email with validation
     */
    public function findByEmailV2(string $email): ?UtilisateurV2
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * ✅ CONTRÔLE DE SAISIE - Find active user by email and role
     */
    public function findByRoleAndEmail(string $role, string $email): ?UtilisateurV2
    {
        if (!in_array($role, ['admin', 'client'])) {
            return null;
        }
        return $this->findOneBy(['email' => trim($email), 'role' => $role]);
    }

    //    /**
    //     * @return UtilisateurV2[] Returns an array of UtilisateurV2 objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?UtilisateurV2
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
