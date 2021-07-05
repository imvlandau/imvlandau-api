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

    /**
     * Find all attendees.
     *
     * @return array|null The entity instances or NULL if the entities can not be found.
     */
    public function countAttendees()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('att')
            ->from('App\Entity\Attendees', 'att');
        $all = $qb->getQuery()->getResult();
        $counter = 0;
        foreach ($all as $key => $attendees) {
            $counter++;
            if (!empty($attendees->getCompanion1())) {
                $counter++;
            }
            if (!empty($attendees->getCompanion2())) {
                $counter++;
            }
            if (!empty($attendees->getCompanion3())) {
                $counter++;
            }
            if (!empty($attendees->getCompanion4())) {
                $counter++;
            }
        }
        return $counter;
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
            // ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        ;

        // $entityManager = $this->getEntityManager();
        // $query = $entityManager->createQuery(
        //   'SELECT att.email
        //   FROM App\Entity\Attendees att
        //   WHERE att.email = :email'
        // )->setParameter('email', $value);
        // return $query->getOneOrNullResult();
    }
    */
}
