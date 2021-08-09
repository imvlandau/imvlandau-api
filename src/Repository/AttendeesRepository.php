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

    /**
     * Truncate attendees table.
     */
    public function truncate()
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $platform   = $connection->getDatabasePlatform();
        $connection->executeUpdate($platform->getTruncateTableSQL('attendees', true));
    }
}
