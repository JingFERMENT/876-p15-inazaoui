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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home()
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/guests', name: 'guests')]
    public function guests(Request $request, UserRepository $userRepository)
    {
        $criteria = [];

        $page = $request->query->getInt('page', 1);
        $limit = 6;
        $offset = $limit * ($page - 1);
        $onlyActive = true;

        $guests = $userRepository->findGuests($limit, $offset, $onlyActive);
        $total = $userRepository->count($criteria);
        
        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
            'total' => $total,
            'limit' => $limit,
            'page'=> $page
        ]);
    }

    #[Route('/guest/{id}', name: 'guest')]
    public function guest(#[MapEntity(id: 'id')] User $guest)
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
        // dd($user);

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez vous identifier.');;
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
