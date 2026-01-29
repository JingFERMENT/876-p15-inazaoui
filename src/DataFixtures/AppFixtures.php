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

        // guest
        $guests = UserFactory::createMany(10);

        // album 1...5
        $albums = [];
        for ($i = 1; $i <= 5; $i++) {
            $albums[] = AlbumFactory::createOne(['name' => 'Album ' . $i]);
        };

        // each media has a user, but 50% times has a media
        MediaFactory::createMany(100, function () use ($guests, $albums) {
            $hasAlbum = random_int(1, 10) <= 5;

            return [
                'user' => $guests[array_rand($guests)],
                'album' => $hasAlbum ? $albums[array_rand($albums)] : null,
            ];
        });

        $manager->flush();
    }
}
