<?php

namespace App\Tests\Api\V2;

use App\Entity\Reservation;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class FoodTrucksControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    private $client;

    /**
     * @var \Doctrine\ORM\EntityManager|object|null
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetDatabase();

        # Avoid memory leaks
        $this->em->close();
        $this->em = null;
    }

    protected function resetDatabase(): void
    {
        $entities = [
            Reservation::class,
        ];

        $connection = $this->em->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->em->getClassMetadata($entity)->getTableName()
            );
            $connection->executeStatement($query);
        }
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /** @test */
    public function it_can_allow_food_truck_to_make_reservation(): void
    {

        $this->client->request('POST', '/api/v2/reservations/save', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-02 10:00',
        ]);

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
    }

    /** @test */
    public function it_must_limit_food_truck_to_make_single_reservations_on_same_day(): void
    {
        $this->client->request('POST', '/api/v2/reservations/save', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-02 10:00',
        ]);

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );

        $this->client->request('POST', '/api/v2/reservations/save', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-02 10:00',
        ]);

        static::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
    }

    /** @test */
    public function it_must_limit_food_truck_to_make_six_reservations_in_week(): void
    {
        $days = [
            '2021-02-01 10:00',
            '2021-02-02 10:00',
            '2021-02-03 10:00',

            '2021-02-01 14:00',
            '2021-02-02 14:00',
            '2021-02-03 14:00',
        ];

        foreach ($days as $date){
            $this->client->request('POST', '/api/v2/reservations/save', [
                'food_truck' => 'FT1',
                'date'       => $date,
            ]);

            static::assertSame(
                Response::HTTP_OK,
                $this->client->getResponse()->getStatusCode(),
                $this->client->getResponse()->getContent()
            );
        }

        $this->client->request('POST', '/api/v2/reservations/save', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-04 10:00',
        ]);

        static::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
    }

    /** @test */
    public function it_must_deny_food_truck_to_make_reservation_on_weekends(): void
    {
        $this->client->request('POST', '/api/v2/reservations/save', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-06 10:00',
        ]);

        static::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );

        $this->client->request('POST', '/api/v2/reservations/save', [
            "food_truck" => "FT1",
            "date"       => '2021-02-07 10:00',
        ]);

        static::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
    }

    /** @test */
    public function it_must_limit_food_trucks_to_make_reservations_in_single_day(): void
    {
        foreach (['2021-02-01 10:00' => 8, '2021-02-01 14:00' => 8, '2021-02-05 10:00' => 7] as $date => $limit) {
            foreach (range(1, $limit) as $number) {
                $this->client->request('POST', '/api/v2/reservations/save', [
                    'food_truck' => 'FT'.$number,
                    'date'       => $date,
                ]);

                static::assertSame(
                    Response::HTTP_OK,
                    $this->client->getResponse()->getStatusCode(),
                    $this->client->getResponse()->getContent()
                );
            }
        }

        foreach (['2021-02-01 10:00', '2021-02-01 14:00'] as $date) {
            $this->client->request('POST', '/api/v2/reservations/save', [
                'food_truck' => 'FT9',
                'date'       => $date,
            ]);

            static::assertSame(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $this->client->getResponse()->getStatusCode(),
                $this->client->getResponse()->getContent()
            );
        }
    }

    /** @test */
    public function it_can_delete_reservation()
    {
        $this->client->request('POST', '/api/v2/reservations/save', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-01 10:00',
        ]);

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );

        $this->client->request('DELETE', '/api/v2/reservations/delete', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-01 10:00',
        ]);

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );

    }

    /** @test */
    public function it_must_throw_exception_on_delete_not_found_reservation()
    {
        $this->client->request('DELETE', '/api/v2/reservations/delete', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-01 10:00',
        ]);

        static::assertSame(
            Response::HTTP_NOT_FOUND,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
    }

    /** @test */
    public function it_can_list_empty_reservations()
    {

        $this->client->request('GET', '/api/v2/reservations');

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode()
        );

        $expected = json_encode([
            'reservations' => [
                'Mon' => [],
                'Tue' => [],
                'Wed' => [],
                'Thu' => [],
                'Fri' => [],
                'Sat' => [],
                'Sun' => [],
            ]
        ]);

        static::assertJsonStringEqualsJsonString($expected, $this->client->getResponse()->getContent());
    }

    /** @test */
    public function it_can_list_all_reservations()
    {
        foreach (range(1, 8) as $number) {
            $this->client->request('POST', '/api/v2/reservations/save', [
                'food_truck' => 'FT'.$number,
                'date'       => '2021-02-01 10:00',
            ]);

            static::assertSame(
                Response::HTTP_OK,
                $this->client->getResponse()->getStatusCode(),
                $this->client->getResponse()->getContent()
            );
        }

        foreach (range(1, 4) as $number) {
            $this->client->request('POST', '/api/v2/reservations/save', [
                'food_truck' => 'FT'.$number,
                'date'       => '2021-02-03 10:00',
            ]);

            static::assertSame(
                Response::HTTP_OK,
                $this->client->getResponse()->getStatusCode(),
                $this->client->getResponse()->getContent()
            );
        }

        $this->client->request('GET', '/api/v2/reservations');

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode()
        );

        $expected = json_encode([
            'reservations' => [
                'Mon' => ['FT1','FT2','FT3','FT4','FT5','FT6','FT7','FT8'],
                'Tue' => [],
                'Wed' => ['FT1','FT2','FT3','FT4'],
                'Thu' => [],
                'Fri' => [],
                'Sat' => [],
                'Sun' => [],
            ]
        ]);

        static::assertJsonStringEqualsJsonString($expected, $this->client->getResponse()->getContent());
    }

    // V2

    /** @test */
    public function it_can_make_reservation_in_same_day_but_different_times()
    {
        $this->client->request('POST', '/api/v2/reservations/save', [
            "food_truck" => 'FT1',
            "date"       => '2021-02-02 10:00',
        ]);

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );

        $this->client->request('POST', '/api/v2/reservations/save', [
            "food_truck" => 'FT1',
            "date"       => '2021-02-02 14:00'
        ]);

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
    }

    /** @test */
    public function it_must_limit_food_truck_to_make_single_reservations_on_same_day_and_same_time_shift(): void
    {
        $this->client->request('POST', '/api/v2/reservations/save', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-02 10:00',
        ]);

        static::assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );

        $this->client->request('POST', '/api/v2/reservations/save', [
            'food_truck' => 'FT1',
            'date'       => '2021-02-02 11:00',
        ]);

        static::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
    }
}