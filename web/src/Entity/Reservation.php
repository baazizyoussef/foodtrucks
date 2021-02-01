<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReservationRepository::class)
 */
class Reservation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $foodTruck;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @return DateTime
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param mixed $date
     *
     * @return Reservation
     */
    public function setId($id): Reservation
    {
        $this->id = $id;

        return $this;
    }


    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     *
     * @return Reservation
     */
    public function setDate(Datetime $date): Reservation
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFoodTruck()
    {
        return $this->foodTruck;
    }

    /**
     * @param mixed $foodTruck
     *
     * @return Reservation
     */
    public function setFoodTruck($foodTruck): Reservation
    {
        $this->foodTruck = $foodTruck;

        return $this;
    }
}
