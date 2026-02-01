<?php

namespace App\DataFixtures;

use App\Factory\AlbumFactory;
use App\Factory\MediaFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // admin
        UserFactory::createOne([
            'name' => 'Ina Zaoui',
            'email' => 'ina@zaoui.com',
            'roles' => ['ROLE_ADMIN'],
        ]);

        // blocked guest
        UserFactory::createOne([
            'name' => 'Block guest',
            'email' => 'blockedGuest@test.com',
            'roles' => ['ROLE_GUEST'],
            'isActive' => false,
        ]);

        // active guest
        $activeGuest = UserFactory::createOne([
            'name' => 'Active guest',
            'email' => 'activeGuest@test.com',
            'roles' => ['ROLE_GUEST'],
            'isActive' => true,
        ]);

        // random active guests
        $randomActiveGuests = UserFactory::createMany(8, ([
            'roles' => ['ROLE_GUEST'],
            'isActive' => true,
        ]));

        $allActiveGuests = array_merge([$activeGuest], $randomActiveGuests);

        // album 1...5
        $albums = [];
        for ($i = 1; $i <= 5; $i++) {
            $albums[] = AlbumFactory::createOne(['name' => 'Album ' . $i]);
        };

        // each media has a user, but 50% times has a media
        MediaFactory::createMany(100, function () use ($allActiveGuests, $albums) {
            $hasAlbum = random_int(1, 10) <= 5;

            return [
                'user' => $allActiveGuests[array_rand($allActiveGuests)],
                'album' => $hasAlbum ? $albums[array_rand($albums)] : null,
            ];
        });

        $manager->flush();
    }
}
