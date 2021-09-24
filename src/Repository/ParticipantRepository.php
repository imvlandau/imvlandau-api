<?php

namespace App\Repository;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Participant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Participant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Participant[]    findAll()
 * @method Participant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * Find all participant.
     *
     * @return array|null The entity instances or NULL if the entities can not be found.
     */
    public function findAll()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('att')
            ->from('App\Entity\Participant', 'att');
        return $qb->getQuery()->getResult();
    }

    /**
     * Find all participant.
     *
     * @return array|null The entity instances or NULL if the entities can not be found.
     */
    public function countParticipants()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('att')
            ->from('App\Entity\Participant', 'att');
        $all = $qb->getQuery()->getResult();
        $counter = 0;
        foreach ($all as $key => $participant) {
            $counter++;
            if (!empty($participant->getCompanion1())) {
                $counter++;
            }
            if (!empty($participant->getCompanion2())) {
                $counter++;
            }
            if (!empty($participant->getCompanion3())) {
                $counter++;
            }
            if (!empty($participant->getCompanion4())) {
                $counter++;
            }
        }
        return $counter;
    }

    /**
     * Truncate participant table.
     */
    public function truncate()
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $platform   = $connection->getDatabasePlatform();
        $connection->executeUpdate($platform->getTruncateTableSQL('participant', true));
    }
}
