<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class GuestController extends AbstractController
{
    #[Route('/admin/guests', name: 'admin_guest_index')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $page = $request->query->getInt('page', 1);
       
        $criteria = [];
        
        $limit = 9;
        $offset = $limit * ($page - 1);
        
        $guests = $userRepository->findGuests($limit, $offset);
        
        $total = $userRepository->count($criteria);
        
        return $this->render(
            '/admin/guest/index.html.twig',
            [
                'guests' => $guests,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        );
    }

    #[Route('/admin/guest/add', name: 'admin_guest_add')]
    public function add(Request $request, EntityManagerInterface $em)
    {
        $user = new User();
        $form = $this->createForm(GuestType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $user->setIsActive(false);
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/guest/{id}/disable', name: 'admin_guest_disable', methods: ['POST'])]
    public function disable(User $guest, Request $request, EntityManagerInterface $em):Response
    {
        if (!$this->isCsrfTokenValid('guest_disable_' . $guest->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $guest->setIsActive(false);
        
        $em->flush();
        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/admin/guest/{id}/enable', name: 'admin_guest_enable', methods: ['POST'])]
    public function enable(User $guest, Request $request, EntityManagerInterface $em):Response
    {
        if (!$this->isCsrfTokenValid('guest_enable_' . $guest->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $guest->setIsActive(true);
        
        $em->flush();
        return $this->redirectToRoute('admin_guest_index');
    }
}
