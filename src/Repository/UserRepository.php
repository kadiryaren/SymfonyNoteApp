<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
/*
    public function getUsers($email,$password){
        $qb = $this->createQueryBuilder('user');
        $qb
            ->select('user.id','user.email','user.password','user.validation')
            ->where('user.email = '.$email.'')
            ->where('user.password = '.$password.'');

        return $qb->getQuery()->getResult();
    }
*/
    public function getUsername($id){
        $qb = $this->createQueryBuilder('user');
        $qb
            ->select('user.username');

        return $qb->getQuery()->getResult();
    }
    public function checkAlreadyExisted($email){
        $qb = $this->createQueryBuilder('user');
        $qb
            ->select('user.id')
            ->where('user.email = '.$email.'');

        return $qb->getQuery()->getResult();
    }
    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
