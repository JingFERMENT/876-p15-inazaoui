<?php

namespace App\Tests\Functional\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MediaPageTest extends WebTestCase
{
    public function testMediaPageRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/media');
        $this->assertResponseRedirects('/login');  
    }

     public function testMediaPageRendersAllMediaListWhenLoggedIn(){
        $client = static::createClient();
        
        $guestRepo = static::getContainer()->get(UserRepository::class);
        $activeGuest = $guestRepo->findOneBy(['email' => 'activeGuest@test.com']);
        $client->loginUser($activeGuest);
        
        $crawler = $client->request('GET', '/admin/media');
        $this->assertResponseIsSuccessful();
        
        // only for guest no invite and album in the nav bar
        $this->assertAnySelectorTextNotContains('a.nav-link', 'Invités');
        $this->assertAnySelectorTextNotContains('a.nav-link', 'Albums');
        
        // add the media 
        $addLink = $crawler->filter('a.btn[href="/admin/media/add"]')->link();
        $client->click($addLink);
        $this->assertResponseIsSuccessful();
        $this->assertSame('/admin/media/add', $client->getRequest()->getPathInfo());

        // delete the media 
        $crawler = $client->request('GET', '/admin/media');
        $this->assertResponseIsSuccessful();
        
        $deleteFormNode = $crawler->filter('form[action^="/admin/media/delete/"]');

        $client->submit($deleteFormNode->form());
        $this->assertResponseRedirects('/admin/media');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
   
    }
}
