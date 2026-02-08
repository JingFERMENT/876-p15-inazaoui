<?php

namespace App\Tests\Functional\Front;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomepageTest extends WebTestCase
{

    public function testHomePageHasPublicNavAndDiscoverLinkRedirectToLoginPage():void
    {

        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('nav');

        $this->assertSelectorExists('nav ul a:contains("Invité")');
        $this->assertSelectorExists('nav ul a:contains("Portfolio")');
        $this->assertSelectorExists('nav ul a:contains("Qui suis-je ?")');
        $this->assertSelectorExists('nav ul a:contains("Connexion")');
        $this->assertSelectorExists('a:contains("découvrir")');
        // Dashboard should not be displayed
        $this->assertSelectorNotExists('nav a:contains("Dashboard")');

        
       $link = $client->getCrawler()->filter('[data-test-id="discover-link"]')->link();
      
        $client->click($link);
        $this->assertResponseRedirects('/login');
        
    }

     public function testHomePageLoggedGuestHasDashboardLinkAndRedirectToDiscoverPage():void 
    {

        $client = static::createClient();

        /** @var UserRepository $guests */
        $guests= static::getContainer()->get(UserRepository::class);

        $activeGuest = $guests->findOneBy(['email' => 'activeGuest@test.com']);
      
        $this->assertNotNull($activeGuest, "L'invité n'est pas trouvé dans la base des données");

        $client->loginUser($activeGuest);

        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('nav');
        $this->assertSelectorExists('nav a:contains("Dashboard")');
        $this->assertSelectorExists('nav a:contains("Déconnexion")');

        $link = $client->getCrawler()->filter('[data-test-id="discover-link"]')->link();
        $client->click($link);
        $this->assertResponseIsSuccessful();
        $this->assertSame('/portfolio', $client->getRequest()->getPathInfo());

    }

}
