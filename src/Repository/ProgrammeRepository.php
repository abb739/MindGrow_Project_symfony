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

    // ─── Filtrage existant (inchangé) ────────────────────────────────────────
    public function findByFilters(string $search = '', string $catId = ''): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.titre LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($catId) {
            $qb->andWhere('p.idCategorie = :catId')
               ->setParameter('catId', (int)$catId);
        }

        return $qb->getQuery()->getResult();
    }

    // ─── Recherche full-text (titre + description) ───────────────────────────
    public function searchFullText(string $q): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.titre LIKE :q OR p.description LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('p.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ─── Programmes avec le nb de séances associées ──────────────────────────
    /**
     * Retourne chaque programme avec le nombre de séances via une requête SQL native.
     * (Les séances n'ont pas de FK ORM vers programme, on fait du SQL natif.)
     */
    public function findAllAvecNbSeances(\Doctrine\DBAL\Connection $conn): array
    {
        $sql = "
            SELECT p.id_programme, p.titre, p.description, p.image, p.video,
                   p.id_categorie,
                   COUNT(s.id_seance) AS nb_seances
            FROM programme p
            LEFT JOIN seance s ON s.titre LIKE CONCAT('%', p.titre, '%')
            GROUP BY p.id_programme
            ORDER BY nb_seances DESC
        ";
        return $conn->fetchAllAssociative($sql);
    }

    // ─── Stats globales ───────────────────────────────────────────────────────
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCategorie(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.idCategorie AS categorie_id, COUNT(p.id) AS total')
            ->groupBy('p.idCategorie')
            ->getQuery()
            ->getArrayResult();
    }

    // ─── Programmes récents ───────────────────────────────────────────────────
    public function findRecents(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // ─── Programmes d'une catégorie ───────────────────────────────────────────
    public function findByCategorie(int $catId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.idCategorie = :catId')
            ->setParameter('catId', $catId)
            ->orderBy('p.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
