<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Conseil>
 */
class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    public function findByMonth($month)
    {
        // $qb = $this->createQueryBuilder('c')
        //     ->where('MONTH(c.createdate) = :month')
        //     // ->where('DATE_PART(\'month\', c.date) = :month')
        //     // ->where("DATE_FORMAT(c.date, '%m') = :month")
        //     // ->andWhere(new Expr\Func('MONTH', ['c.date']) . ' = :month')
        //     ->setParameter('month', $month)
        //     ->getQuery();

        // return $qb->getResult();

        $qb = $this->createQueryBuilder('c');
        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->between(
                    'c.createdate',
                    $qb->expr()->literal(date('Y') . '-' . $month . '-01'),
                    $qb->expr()->literal(date('Y') . '-' . $month . '-31')
                ),
                $qb->expr()->between(
                    'c.updatedate',
                    $qb->expr()->literal(date('Y') . '-' . $month . '-01'),
                    $qb->expr()->literal(date('Y') . '-' . $month . '-31')
                )
            )
        );    
        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Conseil[] Returns an array of Conseil objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Conseil
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
