<?php

namespace App\Tests\Functional\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AlbumPageTest extends WebTestCase
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

     public function testAlbumPageRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/album');
        $this->assertResponseRedirects('/login');  
    }

     public function testGuestPageRequiresAdmin(): void
    {
        
        $client = $this->loginAs('activeGuest@test.com');
        $client->request('GET', '/admin/album');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAlbumPageRendersCorrectly(): void
    {
        $client = $this->loginAs('ina@zaoui.com');

        $crawler = $client->request('GET', '/admin/album');
        $this->assertResponseIsSuccessful();

        $this->assertAnySelectorTextContains('a.nav-link', 'Invités');
        $this->assertAnySelectorTextContains('a.nav-link', 'Albums');

        // add the album
        $addLink = $crawler->filter('a.btn[href="/admin/album/add"]')->link();
        $client->click($addLink);
        $this->assertResponseIsSuccessful();
        $this->assertSame('/admin/album/add', $client->getRequest()->getPathInfo());

        // update the album
        $crawler = $client->request('GET', '/admin/album');
        $this->assertResponseIsSuccessful();
        $updateNodes = $crawler->filter('a.btn[href^="/admin/album/update/"]');
        $this->assertGreaterThan(0, $updateNodes->count(), "Pas d'album à modifier");

        $updateFirstlink = $updateNodes->first()->link();
        $updateFirstHref = $updateNodes->first()->attr('href');
        $client->click($updateFirstlink);
        $this->assertResponseIsSuccessful();
        $this->assertSame($updateFirstHref, $client->getRequest()->getPathInfo());
    
        // delete the album
        $crawler = $client->request('GET', '/admin/album');
        $this->assertResponseIsSuccessful();
        $deleteNodes = $crawler->filter('a.btn[href^="/admin/album/delete/"]');
        $this->assertGreaterThan(0, $deleteNodes->count(), "Pas d'album à supprimer");

        $deleteFirstlink = $updateNodes->first()->link();
        $deleteFirstHref = $updateNodes->first()->attr('href');
        $client->click($deleteFirstlink);
        $this->assertResponseIsSuccessful();
        $this->assertSame($deleteFirstHref, $client->getRequest()->getPathInfo());

    }

}
