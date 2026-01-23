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

final class GuestController extends AbstractController
{
    #[Route('/admin/guest', name: 'admin_guest_index')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $page = $request->query->getInt('page', 1);

        $criteria = [];

        $guests = $userRepository->findBy( $criteria,
            ['id' => 'ASC'],
            9,
            9 * ($page - 1));

        $total = $userRepository->count($criteria);

        return $this->render('/admin/guest/index.html.twig', 
            ['guests' => $guests,
            'total' => $total,
            'page' => $page]);
    }

     #[Route('/admin/guest/add', name: 'admin_guest_add')]
    public function add(Request $request, EntityManagerInterface $em)
    {
        $user= new User();
        $form = $this->createForm(GuestType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', ['form' => $form->createView()]);
    }
}
