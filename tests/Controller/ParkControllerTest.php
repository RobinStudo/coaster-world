<?php

namespace App\Tests\Controller;

use App\Repository\ParkRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ParkControllerTest extends WebTestCase
{
    const PARK_NAME = 'Europa Test';

    public function testCreate(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([
            'email' => 'admin@monde.com',
        ]);
        $client->loginUser($user);

        $client->request('GET', '/park/create');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'park[name]' => self::PARK_NAME,
            'park[imageFile]' => 'assets/images/logo.png',
            'park[type]' => 0,
            'park[address][city]' => 'Lille',
            'park[address][country]' => 'CH',
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('h1', self::PARK_NAME);
    }

    /**
     * @dataProvider provideUsers
     */
    public function testDeleteSecurity(?string $userEmail): void
    {
        $client = static::createClient();

        if ($userEmail !== null) {
            $userRepository = static::getContainer()->get(UserRepository::class);
            $user = $userRepository->findOneBy([
                'email' => $userEmail,
            ]);
            $client->loginUser($user);
        }

        $parkRepository = static::getContainer()->get(ParkRepository::class);
        $park = $parkRepository->findOneBy([
            'name' => self::PARK_NAME,
        ], ['id' => 'DESC']);

        $client->request('GET', sprintf('/park/%s/delete', $park->getId()));

        if ($userEmail !== null) {
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        } else {
            $this->assertResponseRedirects('/login');
        }
    }

    /**
     * @depends testDeleteSecurity
     */
    public function testDelete(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([
            'email' => 'admin@monde.com',
        ]);
        $client->loginUser($user);

        $parkRepository = static::getContainer()->get(ParkRepository::class);
        $park = $parkRepository->findOneBy([
            'name' => self::PARK_NAME,
        ], ['id' => 'DESC']);

        $client->request('GET', sprintf('/park/%s', $park->getId()));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('a.park-delete-link', 'Supprimer le parc');

        $client->clickLink('Supprimer le parc');

        $this->assertResponseRedirects();
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertNull($parkRepository->find($park->getId()));
    }

    private function provideUsers(): array
    {
        return [
            [null],
            ['bonjour@monde.com'],
            ['contributor@monde.com'],
            ['moderator@monde.com']
        ];
    }
}
