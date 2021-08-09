<?php

namespace App\Repository;

use App\Entity\Settings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
// use GuzzleHttp\Psr7\Query;
// use Doctrine\ORM\Query;

/**
 * @method Settings|null find($id, $lockMode = null, $lockVersion = null)
 * @method Settings|null findOneBy(array $criteria, array $orderBy = null)
 * @method Settings[]    findAll()
 * @method Settings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settings::class);
    }

    /**
     * Find all settings.
     *
     * @return array|null The entity instances or NULL if the entities can not be found.
     */
    public function findAll()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('att')
            ->from('App\Entity\Settings', 'att');
        return $qb->getQuery()->getResult();
    }

    /**
     * Get first of settings.
     *
     * @return Settings|null The entity instances or NULL if the entities can not be found.
     */
    public function getFirst()
    {
        $result = $this->findAll();
        return (!empty($result)) ? $result[0] : null;
    }
}
