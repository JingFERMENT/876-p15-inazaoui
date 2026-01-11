<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home()
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/guests', name: 'guests')]
    public function guests(UserRepository $users)
    {
        $guests = $users->findBy(['admin' => false]);
        return $this->render('front/guests.html.twig', [
            'guests' => $guests
        ]);
    }

    #[Route('/guest/{id}', name: 'guest')]
    public function guest(#[MapEntity(id: 'id')] User $guest)
    {
        return $this->render('front/guest.html.twig', [
            'guest' => $guest
        ]);
    }

    #[Route('/portfolio/{id}', name: 'portfolio', defaults: ['id' => null])]
    public function portfolio(
        AlbumRepository $albumsRepo,
        UserRepository $usersRepo,
        MediaRepository $mediasRepo,
        #[MapEntity(id: 'id')] ?Album $album = null,
    ) {
        $albums = $albumsRepo->findAll();
        $user = $usersRepo->findOneByAdmin(true);

        if (!$user) {
            throw $this->createNotFoundException('Admin user not found.');
        }
        $medias = $album
            ? $mediasRepo->findByAlbum($album)
            : $mediasRepo->findByUser($user);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about()
    {
        return $this->render('front/about.html.twig');
    }
}
