<?php

namespace App\Repository;

use App\Entity\Abonnement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Abonnement::class);
    }

    /**
     * Tri intelligent : 12m → 6m → 3m → 24m → reste, puis par prix ASC
     */
    public function findAllOrderedByPrix(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('
                CASE
                    WHEN a.dureeMois = 12 THEN 1
                    WHEN a.dureeMois = 6  THEN 2
                    WHEN a.dureeMois = 3  THEN 3
                    WHEN a.dureeMois = 24 THEN 4
                    ELSE 5
                END
            ', 'ASC')
            ->addOrderBy('a.prix', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ NOUVEAU : Filtre combiné — recherche texte + durée + tri
     *
     * @param string $q      Recherche dans nom ou description
     * @param int    $duree  Filtrer par durée exacte (0 = toutes)
     * @param string $tri    populaire | prix_asc | prix_desc | duree_asc | duree_desc
     */
    public function findByFilters(string $q = '', int $duree = 0, string $tri = 'populaire'): array
    {
        $qb = $this->createQueryBuilder('a');

        // Filtre texte
        if ($q !== '') {
            $qb->andWhere('a.nom LIKE :q OR a.description LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        // Filtre durée
        if ($duree > 0) {
            $qb->andWhere('a.dureeMois = :duree')
               ->setParameter('duree', $duree);
        }

        // Tri
        switch ($tri) {
            case 'prix_asc':
                $qb->orderBy('a.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('a.prix', 'DESC');
                break;
            case 'duree_asc':
                $qb->orderBy('a.dureeMois', 'ASC')->addOrderBy('a.prix', 'ASC');
                break;
            case 'duree_desc':
                $qb->orderBy('a.dureeMois', 'DESC')->addOrderBy('a.prix', 'ASC');
                break;
            case 'populaire':
            default:
                $qb->orderBy('
                    CASE
                        WHEN a.dureeMois = 12 THEN 1
                        WHEN a.dureeMois = 6  THEN 2
                        WHEN a.dureeMois = 3  THEN 3
                        WHEN a.dureeMois = 24 THEN 4
                        ELSE 5
                    END
                ', 'ASC')->addOrderBy('a.prix', 'ASC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche simple par nom ou description (gardée pour compatibilité)
     */
    public function findBySearch(string $q): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.nom LIKE :q OR a.description LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('a.prix', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Abonnements dans une fourchette de prix
     */
    public function findByPriceRange(float $min, float $max): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.prix BETWEEN :min AND :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->orderBy('a.prix', 'ASC')
            ->getQuery()
            ->getResult();
    }
}