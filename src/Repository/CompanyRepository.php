<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    //    /**
    //     * @return Company[] Returns an array of Company objects
    //     */
    public function listCompany($data)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('DISTINCT c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->leftJoin('c.tags', 't')
            ->where('c.removeAt IS NULL');



        // 🔍 Recherche globale
        if (!empty($data['contains'])) {
            $qb->andWhere('p.value LIKE :q')
                ->andWhere('m.identifier = true')
                ->setParameter('q', '%' . $data['contains'] . '%');
        }

        // 👨‍💼 Manager
        if (!empty($data['managers'])) {
            if (is_array($data['managers'])) {
                $qb->andWhere('c.manager IN (:managers)')
                    ->setParameter('managers', $data['managers']);
            } else {
                $qb->andWhere('c.manager = :manager')
                    ->setParameter('manager', $data['managers']);
            }
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


        if (!empty($data['tags'])) {
            if (is_array($data['tags'])) {
                $qb->andWhere('t.id IN (:tags)')
                    ->setParameter('tags', $data['tags']);
            } else {
                $qb->andWhere('t.id = :tag')
                    ->setParameter('tag', $data['tags']);
            }
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
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('c.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getCount()
    {
        $qb = $this->createQueryBuilder('c');
        return $qb->select($qb->expr()->countDistinct('c.id'))
            ->andWhere('c.removeAt IS NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getDeletedCount(): int
    {
        $qb = $this->createQueryBuilder('c');
        return $qb->select($qb->expr()->countDistinct('c.id'))
            ->andWhere('c.removeAt IS NOT NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function getAllCount(): int
    {
        $qb = $this->createQueryBuilder('c');
        return $qb->select($qb->expr()->countDistinct('c.id'))
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Company[] Returns an array of deleted Company objects
     */
    public function listDeletedCompany($data)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('DISTINCT c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->where('c.removeAt IS NOT NULL');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de suppression (du plus récent au plus ancien)
        $qb->orderBy('c.removeAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Company[] Returns an array of all Company objects (active + deleted)
     */
    public function listAllCompany($data)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('DISTINCT c')
            ->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm');

        // 📄 Pagination
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('c.createdAt', 'DESC');

        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function searchCompanies($data)
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

        // 📋 Tri par date de création (du plus récent au plus ancien)
        $qb->orderBy('c.createdAt', 'DESC');

        // Limite à 50 résultats pour les recherches
        $qb->setMaxResults(50);

        return $qb->getQuery()->getResult();
    }

    //    public function findOneBySomeField($value): ?Company
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
