<?php

namespace App\Repository;

use App\Entity\StartupScript;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method StartupScript|null find($id, $lockMode = null, $lockVersion = null)
 * @method StartupScript|null findOneBy(array $criteria, array $orderBy = null)
 * @method StartupScript[]    findAll()
 * @method StartupScript[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StartupScriptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StartupScript::class);
    }

    /**
     * Find all startup scripts.
     *
     * @return array|null The entity instances or NULL if the entities can not be found.
     */
    public function findAll()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('ss.id, ss.name, ss.content')
            ->from('App\Entity\StartupScript', 'ss');
        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return StartupScript[] Returns an array of StartupScript objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('ss.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('ss.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StartupScript
    {
        return $this->createQueryBuilder('s')
            ->andWhere('ss.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
