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

        $qb->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->where('c.removeAt IS NULL');



        // 🔍 Recherche globale
        if (!empty($data['contains'])) {
            $qb->andWhere('p.value LIKE :q')
                ->andWhere('m.identifier = true')
                ->setParameter('q', '%' . $data['contains'] . '%');
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
        $page = max((int)($data['pagination']['page'] ?? 1), 1);
        $limit = min((int)($data['pagination']['limit'] ?? 25), 100);
        $offset = ($page - 1) * $limit;

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


    public function searchCompanies($data)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->leftJoin('c.properties', 'p')
            ->leftJoin('p.propertyModel', 'm')
            ->where('c.removeAt IS NULL');

        // 🔍 Recherche globale
        if (!empty($data)) {
            $qb->andWhere('p.value LIKE :q')
                ->setParameter('q', '%' . $data . '%');
        }

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
