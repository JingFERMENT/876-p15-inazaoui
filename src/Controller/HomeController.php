<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home()
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/guests', name: 'guests')]
    public function guests(UserRepository $userRepository, CacheInterface $cache)
    {
        $guests = $cache->get('guests_with_media_count', function (ItemInterface $item) use ($userRepository) {
            $item->expiresAfter(300);
            return $userRepository->findForActiveGuestsWithMediaCount();
        });

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
        ]);
    }

    #[Route('/guest/{id}', name: 'guest', requirements: ['id' => '\d+'])]
    public function guest(#[MapEntity(id: 'id')] User $guest): Response
    {
        return $this->render('front/guest.html.twig', [
            'guest' => $guest
        ]);
    }

    #[Route('/portfolio/{id}', name: 'portfolio', defaults: ['id' => null], requirements: ['id' => '\d+'])]
    public function portfolio(
        AlbumRepository $albumsRepo,
        MediaRepository $mediasRepo,
        Security $security,
        #[MapEntity(id: 'id')] ?Album $album = null,
    ) {
        $albums = $albumsRepo->findAll();

        $user = $security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez vous identifier.');
        }

        $medias = $album
            ? $mediasRepo->findByAlbum($album)
            : $mediasRepo->findForActiveGuests();

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
