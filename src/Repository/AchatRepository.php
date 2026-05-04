<?php

namespace App\Repository;

use App\Entity\Achat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AchatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Achat::class);
    }

    /**
     * Trouver l'abonnement actif d'un utilisateur
     */
    public function findAbonnementActifByUser(int $userId): ?Achat
    {
        return $this->createQueryBuilder('a')
            ->where('a.idUtilisateur = :uid AND a.statut = :statut')
            ->setParameter('uid', $userId)
            ->setParameter('statut', 'actif')
            ->orderBy('a.dateAchat', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * ✅ CORRIGÉ : Stats globales
     * - Retourne 0/0/0 si aucun abonné actif → stats masquées dans la vue
     * - COUNT(DISTINCT id_utilisateur) : 1 user = 1 abonné même s'il a souscrit plusieurs fois
     */
    public function getStatsAbonnements(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Abonnés uniques actifs
        $actifs = (int)$conn->fetchOne(
            "SELECT COUNT(DISTINCT id_utilisateur) FROM achat WHERE statut = 'actif'"
        );

        // Aucun actif → tout à zéro (la vue n'affichera rien)
        if ($actifs === 0) {
            return ['total_achats' => 0, 'actifs' => 0, 'revenu_total' => 0];
        }

        // Total toutes souscriptions
        $total = (int)$conn->fetchOne(
            "SELECT COUNT(*) FROM achat WHERE statut IN ('actif', 'annulé')"
        );

        // Revenus actifs
        $revenu = (float)$conn->fetchOne("
            SELECT COALESCE(SUM(ab.prix), 0)
            FROM achat a
            JOIN abonnement ab ON ab.id_abonnement = a.id_abonnement
            WHERE a.statut = 'actif'
        ");

        return [
            'total_achats' => $total,
            'actifs'       => $actifs,
            'revenu_total' => $revenu,
        ];
    }

    /**
     * Stats détaillées par abonnement (admin)
     */
    public function getStatsParAbonnement(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        return $conn->fetchAllAssociative("
            SELECT
                ab.id_abonnement AS id,
                ab.nom,
                ab.prix,
                COUNT(a.id_achat)                                                       AS total,
                SUM(CASE WHEN a.statut = 'actif'  THEN 1 ELSE 0 END)                   AS actifs,
                SUM(CASE WHEN a.statut = 'annulé' THEN 1 ELSE 0 END)                   AS annules,
                COALESCE(SUM(CASE WHEN a.statut = 'actif' THEN ab.prix ELSE 0 END), 0) AS revenu
            FROM abonnement ab
            LEFT JOIN achat a ON a.id_abonnement = ab.id_abonnement
            GROUP BY ab.id_abonnement, ab.nom, ab.prix
            ORDER BY revenu DESC
        ");
    }

    /**
     * Historique des achats d'un utilisateur
     */
    public function findHistoriqueByUser(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.idUtilisateur = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('a.dateAchat', 'DESC')
            ->getQuery()
            ->getResult();
    }
}