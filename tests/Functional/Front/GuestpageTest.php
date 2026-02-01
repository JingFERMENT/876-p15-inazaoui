<?php

namespace App\Tests\Functional\Front;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GuestpageTest extends WebTestCase
{

    public function testGuestsPageListsGuestsWithDiscoverLinks(): void
    {
        // open guests list
        $client = static::createClient();

        $crawler = $client->request('GET', '/guests');
        
        $this->assertResponseIsSuccessful();

        // assert the page title
        $this->assertSelectorTextContains('h3', 'Invités');

        $this->assertGreaterThan(0, $crawler->filter('.guests .guest')->count());

        // title (character + space + digital)
        $firstGuestTitle = trim($crawler->filter('.guests .guest h4')->first()->text());
        $this->assertMatchesRegularExpression('/^.+\s\(\d+\)$/', $firstGuestTitle);

        $firstLink = $crawler
            ->filter('.guests .guest a:contains("découvrir")')
            ->first()
            ->link();

        $firstHref = $firstLink->getUri();
        
        $this->assertStringContainsString('/guest/', $firstHref);

        // open the discover link
        $client->click($firstLink);
        $this->assertResponseIsSuccessful();

        $this->assertMatchesRegularExpression('#^/guest/\d+$#', $client->getRequest()->getPathInfo());
    }

    public function testGuestPagesDoesNotShowBlockedGuests(){
         // open guests list
        $client = static::createClient();

        /** @var UserRepository $usersRepo */
        $usersRepo= static::getContainer()->get(UserRepository::class);
        $blockedGuest = $usersRepo->findOneBy(['email'=> 'blockedGuest@test.com']);
        $this->assertNotNull($blockedGuest, "L'invité bloqué n'est pas trouvé dans la base des données");

        
        $crawler = $client->request('GET', '/guests');
        $this->assertResponseIsSuccessful();

        $idOfBlockedGuest = $blockedGuest->getId();
        $selector = sprintf('.guest .guest[data-guest-id="%d"]', $idOfBlockedGuest);
        
        $this->assertCount(0, $crawler->filter($selector));

    }
}
