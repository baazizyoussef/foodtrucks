<?php

namespace App\Controller\Api\V2;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FoodTrucksController extends AbstractController
{
    /**
     * Liste all Reservations
     *
     * @Route("/api/v2/reservations", methods={"GET"})
     *
     * @param ReservationRepository $repo
     * @return JsonResponse
     */
    public function index(ReservationRepository $repo)
    {
        $reservations = $repo->findAll();

        $days = [
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
            'Sun',
        ];

        return new JsonResponse([
            'reservations' => array_map(function ($day) use ($reservations) {
                $reservations = array_filter($reservations, function ($reservation) use ($day) {
                    return $reservation->getDate()->format('D') == $day;
                });

                return array_values(array_map(function ($reservation) {
                    return $reservation->getFoodTruck();
                }, $reservations));
            }, array_combine($days, $days)),
        ]);
    }

    /**
     * Save Reservation
     *
     * @Route("/api/v2/reservations/save", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function save(Request $request, ReservationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $foodTruck = $request->request->get('food_truck');
        $date = new \DateTime($request->request->get('date'));

        // Un foodtruck ne peut réserver que 3 emplacements sur une semaine
        $reservations = $repo->getAllByFoodtruckByWeek($date, $foodTruck);

        $reservationsCount = count(array_filter($reservations, function ($reservation) use ($date) {
            return $reservation->getDate()->format('A') == $date->format('A');
        }));

        if ($reservationsCount >= 3)
            return $this->errorResponse("Depassement du nombre des reservations par semaine!");

        // Un foodtruck ne peut réserver qu'un seul emplacement par jour
        foreach ($reservations as $reserved) {
            if ($this->isSameTimeShift($reserved, $date))
                return $this->errorResponse("Deja reservé!");
        }

        // Verification si le food truck a une reservation
        $limit = $this->getCalendarLimit($date->format('D'));

        // Aucune réservation n'est possible le samedi et le dimanche
        if ($limit === 0)
            return $this->errorResponse("Impossible de reserver en weekend!");

        //Il y a 8 emplacements disponibles du lundi au jeudi et seulement 7 le vendredi
        $reservations = $repo->getAllByTimeShift($date);

        if (count($reservations) >= $limit)
            return $this->errorResponse("Tous les emplacements sont reserves!");

        $reservation =  (new Reservation)
            ->setFoodTruck($foodTruck)
            ->setDate($date);

        $em->persist($reservation);
        $em->flush();

         return new JsonResponse([
             'code' => 'success',
         ], JsonResponse::HTTP_OK);
    }

    /**
     * Delete Reservation
     *
     * @Route("/api/v2/reservations/delete", methods={"DELETE"})
     *
     * @param Request $request
     * @param ReservationRepository $repo
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function delete(Request $request, ReservationRepository $repo, EntityManagerInterface $em): JsonResponse
    {

        $reservation = $repo->findOneBy([
            'foodTruck' => $request->request->get('food_truck'),
            'date'      => new \DateTime($request->request->get('date')),
        ]);

        if (is_null($reservation))
            return $this->errorResponse("Reservation non trouvee!", JsonResponse::HTTP_NOT_FOUND);

        $em->remove($reservation);
        $em->flush();

        return new JsonResponse([
            'code' => 'success',
        ], JsonResponse::HTTP_OK);
    }

    protected function errorResponse(string $message, int $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return new JsonResponse([
            'code'    => 'error',
            'message' => $message,
        ], $status );
    }

    protected function getCalendarLimit(string $day):int
    {
        return $this->calendarLimits()[$day];
    }

    protected function calendarLimits(): array
    {
        return [
            'Mon' => 8,
            'Tue' => 8,
            'Wed' => 8,
            'Thu' => 8,
            'Fri' => 7,
            'Sat' => 0,
            'Sun' => 0,
        ];
    }

    private function isSameTimeShift(Reservation $reserved, \DateTime $date): bool
    {
        // Check Date
        if ($reserved->getDate()->format('Y-m-d') !== $date->format('Y-m-d'))
            return false;

        $isPastNoon = $reserved->getDate()->format('A') === 'PM';

        // Check Time
        $time = (int) $date->format('H');

        return $isPastNoon
            ? ($time > 12)
            : ($time <= 12);
    }
}
