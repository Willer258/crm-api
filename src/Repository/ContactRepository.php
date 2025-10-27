<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * @return Contact[] Returns an array of Contact objects
     */
    public function listContacts($data)
    {
        $qb = $this->createQueryBuilder('c');

        

        $qb->select('DISTINCT c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->leftJoin('c.company', 'co')
            ->leftJoin('c.tags', 't')
            ->where('c.removeAt IS NULL');



        // 🔍 Recherche globale
        if (!empty($data['contains'])) {
            $qb->andWhere('p.value LIKE :q')
                ->andWhere('m.identifier = true')
                ->setParameter('q', '%' . $data['contains'] . '%');
        }

        // 🏢 Société
        if (!empty($data['company'])) {
            $qb->andWhere('co.id = :company')
                ->setParameter('company', $data['company']);
        }

        // 👨‍💼 Manager
        if (!empty($data['manager'])) {
            $qb->andWhere('u.id = :manager')
                ->setParameter('manager', $data['manager']);
        }

        // 📅 Dates
        if (!empty($data['start_date'])) {
            $qb->andWhere('c.createdAt >= :start')
                ->setParameter('start', new \DateTime($data['start_date']));
        }

        if (!empty($data['end_date'])) {
            $qb->andWhere('c.createdAt <= :end')
                ->setParameter('end', new \DateTime($data['end_date'] . ' 23:59:59'));
        }

        // 🔍 Filtres dynamiques
        if (!empty($data['properties']) && is_array($data['properties'])) {
            $i = 0;
            foreach ($data['properties'] as $filter) {
                $prop = $filter['property'];
                $op = strtolower($filter['operator']);
                $val = $filter['value'];

                $alias = 'p_' . $i;
                $modelAlias = 'm_' . $i;

                $qb->leftJoin('c.properties', $alias)
                    ->leftJoin("$alias.propertyModel", $modelAlias)
                    ->andWhere("$modelAlias.name = :prop_$i");

                if ($op === 'like') {
                    $qb->andWhere("$alias.value LIKE :val_$i")
                        ->setParameter("val_$i", "%$val%");
                } else {
                    $qb->andWhere("$alias.value $op :val_$i")
                        ->setParameter("val_$i", $val);
                }

                $qb->setParameter("prop_$i", $prop);
                $i++;
            }
        }

        // 📄 Pagination
        $page =$data['pagination']['page'];
        $limit =$data['pagination']['limit'];
        $offset = ($page - 1) * $limit;


        $qb->setFirstResult($offset)->setMaxResults($limit);

        $contacts = $qb->getQuery()->getResult();


        return $contacts;   

    }


    public function getCount (){
        $qb = $this->createQueryBuilder('c');
        return $qb->select($qb->expr()->countDistinct('c.id'))
            ->andWhere('c.removeAt IS NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function searchContacts($data)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('DISTINCT c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->where('c.removeAt IS NULL');

        // 🔍 Recherche globale
        if (!empty($data)) {
            $qb->andWhere('p.value LIKE :q')
                ->setParameter('q', '%' . $data . '%');
        }

        // Limite à 50 résultats pour les recherches
        $qb->setMaxResults(50);

        return $qb->getQuery()->getResult();
    }

    //    public function findOneBySomeField($value): ?Contact
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
