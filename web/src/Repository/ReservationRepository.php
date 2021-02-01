<?php

namespace App\Repository;

use App\Entity\Reservation;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    // /**
    //  * @return Reservation[] Returns an array of Reservation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @param DateTime $date
     * @return \App\Entity\Reservation[]|mixed
     */
    public function getAllByDay(DateTime $date): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.date = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @param string $foodTruck
     *  @return \App\Entity\Reservation[]|mixed
     */
    public function getAllByFoodtruck(string $foodTruck)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.foodTruck = :foodTruck')
            ->setParameter('foodTruck', $foodTruck)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @param DateTime $date
     * @param string $foodTruck
     *
     * @return Reservation[]|mixed
     */
    public function getAllByFoodtruckByWeek(DateTime $date, string $foodTruck)
    {
        $dto = (new DateTime)
            ->setISODate($date->format('Y'), $date->format('W'));

        $startWeek = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $endWeek = $dto->format('Y-m-d');

        return $this->createQueryBuilder('r')
            ->andWhere('r.foodTruck = :foodTruck')
            ->andWhere('r.date >= :start')
            ->andWhere('r.date <= :end')
            ->setParameter('foodTruck', $foodTruck)
            ->setParameter('start',$startWeek)
            ->setParameter('end',$endWeek)
            ->getQuery()
            ->getResult()
            ;
    }

    public function getAllByTimeShift(DateTime $date)
    {
        $isPastNoon = $date->format('A') === 'PM';
        $startShift = $isPastNoon
            ? (clone $date)->setTime(12, 0)
            : (clone $date)->setTime(0, 0);
        $endShift = $isPastNoon
            ? (clone $date)->setTime(23, 59, 59)
            : (clone $date)->setTime(11, 59, 59);

        return $this->createQueryBuilder('r')
            ->andWhere('r.date >= :start')
            ->andWhere('r.date <= :end')
            ->setParameter('start', $startShift)
            ->setParameter('end', $endShift)
            ->getQuery()
            ->getResult()
            ;
    }
}
