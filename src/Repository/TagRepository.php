<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Service\WorkspaceResolver;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends WorkspaceAwareRepository<Tag>
 */
class TagRepository extends WorkspaceAwareRepository
{
    public function __construct(ManagerRegistry $registry, WorkspaceResolver $workspaceResolver)
    {
        parent::__construct($registry, Tag::class, $workspaceResolver);
    }

    /**
     * Trouve ou crée un tag par son code
     * Utilisé pour la synchronisation depuis Form
     */
    public function findOrCreateByCode(string $code): Tag
    {
        $tag = $this->findOneBy(['code' => $code]);

        if (!$tag) {
            $tag = new Tag();
            $tag->setCode($code);
            // Générer un label à partir du code (nouveau_client -> Nouveau client)
            $label = ucfirst(str_replace('_', ' ', $code));
            $tag->setLabel($label);
            // Couleur par défaut
            $tag->setColor($this->getDefaultColorForCode($code));

            $em = $this->getEntityManager();
            $em->persist($tag);
            $em->flush();
        }

        return $tag;
    }

    /**
     * Retourne une couleur par défaut selon le code du tag
     */
    private function getDefaultColorForCode(string $code): string
    {
        return match($code) {
            'nouveau_client' => '#28a745', // vert
            'client_habituel' => '#007bff', // bleu
            'chaud' => '#dc3545', // rouge
            'tres_chaud' => '#ff0000', // rouge vif
            'froid' => '#6c757d', // gris
            'interesse' => '#ffc107', // jaune
            'devis_demande' => '#17a2b8', // cyan
            'en_comparaison' => '#17a2b8', // cyan
            'en_prospection' => '#6610f2', // violet
            'proposition_envoyee' => '#fd7e14', // orange
            'en_negociation' => '#fd7e14', // orange
            'gagne' => '#28a745', // vert
            'perdu' => '#6c757d', // gris
            'abandonne' => '#6c757d', // gris
            'expire' => '#6c757d', // gris
            default => '#6c757d' // gris par défaut
        };
    }

    //    /**
    //     * @return Tag[] Returns an array of Tag objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tag
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
