<?php

namespace App\Repository;

use App\Entity\Attendees;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Attendees|null find($id, $lockMode = null, $lockVersion = null)
 * @method Attendees|null findOneBy(array $criteria, array $orderBy = null)
 * @method Attendees[]    findAll()
 * @method Attendees[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttendeesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attendees::class);
    }

    /**
     * Find all attendees.
     *
     * @return array|null The entity instances or NULL if the entities can not be found.
     */
    public function findAll()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('att')
            ->from('App\Entity\Attendees', 'att');
        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return Attendees[] Returns an array of Attendees objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('att')
            ->andWhere('att.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('att.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Attendees
    {
        return $this->createQueryBuilder('att')
            ->andWhere('att.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
