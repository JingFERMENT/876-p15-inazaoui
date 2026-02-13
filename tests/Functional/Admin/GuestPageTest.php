<?php

namespace App\Tests\Functional\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GuestPageTest extends WebTestCase
{
   
    private function loginAs(string $email): KernelBrowser
    {
        $client = static::createClient();

        $guestRepo = static::getContainer()->get(UserRepository::class);
        $user = $guestRepo->findOneBy(['email' => $email]);
        self::assertNotNull($user, "Pas d'utilisateur: $email");

        $client->loginUser($user);

        return $client;
    }

    public function testGuestPageRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/guests');
        $this->assertResponseRedirects('/login');  
    }

    public function testGuestPageRequiresAdmin(): void
    {
        
        $client = $this->loginAs('activeGuest@test.com');
        $client->request('GET', '/admin/guests');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGuestPageRendersCorrectly(): void
    {
        $client = $this->loginAs('ina@zaoui.com');

        $crawler = $client->request('GET', '/admin/guests');
        $this->assertResponseIsSuccessful();

        $this->assertAnySelectorTextContains('a.nav-link', 'Invités');
        $this->assertAnySelectorTextContains('a.nav-link', 'Albums');

        // add the guest
        $addLink = $crawler->filter('a.btn[href="/admin/guest/add"]')->link();
        $client->click($addLink);
        $this->assertResponseIsSuccessful();
        $this->assertSame('/admin/guest/add', $client->getRequest()->getPathInfo());

        // block the guest
        $crawler = $client->request('GET', '/admin/guests');
        $this->assertResponseIsSuccessful();
        $blockFormNode = $crawler->filter('form[action^="/admin/guest/disable/"]');
        $this->assertGreaterThan(0, $blockFormNode->count(), "Pas d'invité à bloquer");

        $client->submit($blockFormNode->form());
        $this->assertResponseRedirects('/admin/guests');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // unblock the guest
        $crawler = $client->request('GET', '/admin/guests');
        $this->assertResponseIsSuccessful();
        $unblockFormNode = $crawler->filter('form[action^="/admin/guest/enable/"]');
        $this->assertGreaterThan(0, $unblockFormNode->count(), "Pas d'invité à débloquer");

        $client->submit($unblockFormNode->form());
        $this->assertResponseRedirects('/admin/guests');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // delete the guest
        $crawler = $client->request('GET', '/admin/guests');
        $this->assertResponseIsSuccessful();
        $deleteFormNode = $crawler->filter('form[action^="/admin/guest/delete/"]');
        $this->assertGreaterThan(0, $unblockFormNode->count(), "Pas d'invité à supprimer");

        $client->submit($deleteFormNode->form());
        $this->assertResponseRedirects('/admin/guests');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testSetPasswordPageRendersAndActivateGuests(){

        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $guest = new User();
        $guest->setEmail('test@test.com');
        $guest->setName('testSetPwd');
        $guest->setRoles(['ROLE_USER']);
        $guest->setIsActive(false);

        $token = bin2hex(random_bytes(32));
        $guest->setInvitationToken($token);
        $guest->setInvitationExpiredAt(new DateTimeImmutable('+2 days'));
        $em->persist($guest);
        $em->flush();

        $client->request('GET', '/set-password/'.$token);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $client->submitForm('Activer ton compte', [
            'set_password[plainPassword][first]' => 'Password123@',
            'set_password[plainPassword][second]' => 'Password123@',
        ]);

        $this->assertResponseRedirects('/');

        $em->clear(); // reload from DB

        $reloaded = $em->getRepository(User::class)->findOneBy(['email' => 'test@test.com']);

        $this->assertNotNull($reloaded);

        $this->assertTrue($reloaded->isActive());
        $this->assertNull($reloaded->getInvitationExpiredAt());
        $this->assertNull($reloaded->getInvitationToken());

    }
    

    
}
