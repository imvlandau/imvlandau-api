<?php

namespace App\Repository;

use App\Entity\KeyPair;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method KeyPair|null find($id, $lockMode = null, $lockVersion = null)
 * @method KeyPair|null findOneBy(array $criteria, array $orderBy = null)
 * @method KeyPair[]    findAll()
 * @method KeyPair[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KeyPairRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeyPair::class);
    }

    /**
     * Find all keypairs.
     *
     * @return array|null The entity instances or NULL if the entities can not be found.
     */
    public function findAll()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('kp.id, kp.name, kp.fingerprint')
            ->from('App\Entity\KeyPair', 'kp');
        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return KeyPair[] Returns an array of KeyPair objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('k.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?KeyPair
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
