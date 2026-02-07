<?php

namespace App\Tests\Functional\Front;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class PortfolioPageTest extends WebTestCase
{
    public function testPortfolioRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/portfolio');
        $this->assertResponseRedirects('/login');
    }

    public function testPortfolioPageRendersAlbumsAndMediaWhenLoggedIn()
    {
        $client = static::createClient();

        $guestsRepo = static::getContainer()->get(UserRepository::class);

        $activeGuest = $guestsRepo->findOneBy(['email' => 'activeGuest@test.com']);

        //logged user 
        $client->loginUser($activeGuest);
        $crawler = $client->request('GET', '/portfolio');
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h3', 'Portfolio');

        $albumsBouttons = $crawler->filter('.mb-5 a.btn');
        
        $albumsTexts = $albumsBouttons->each(fn(Crawler $a)=>trim($a->text()));

        $this->assertContains('Toutes', $albumsTexts);
        $this->assertGreaterThan(0, $albumsBouttons->count());

        $albumsRepo = static::getContainer()->get(AlbumRepository::class);

        $albums = $albumsRepo->findAll();
        $this->assertNotEmpty($albums, "Pas d'album trouvé dans la base des données");
        
        foreach ($albums as $album) {
            $this->assertContains($album->getName(), $albumsTexts);
        }

        // active albumButton when album is null
        $activeAlbumText=$crawler->filter('.mb-5 a.btn.active')->text();
        $this->assertSame('Toutes', $activeAlbumText);

        //media
        $mediaCards = $crawler->filter('.media');
        
        // media number > 0
        $this->assertGreaterThan(0, $mediaCards->count(), "Pas de média trouvé sur la page de portfolio.");

        foreach($mediaCards as $mediaCard) {
            //image
            $card = new Crawler($mediaCard);
            $this->assertGreaterThan(0, $card->filter('img')->count());
            $src = $card->filter('img')->attr('src');
            $this->assertNotSame('', trim($src), "Le media n'a pas d'image");
            $this->assertStringNotContainsString('/uploads/0099.jpg', $src);
            
            //media title
            $this->assertGreaterThan(0, $card->filter('.media-title')->count(), "Le Média n'a pas de titre.");
            $this->assertAnySelectorTextContains('.media-title', 'VISIBLE_MEDIA_FOR_ACTIVE_GUEST');
            $this->assertAnySelectorTextNotContains('.media-title', 'HIDDEN_MEDIA_FOR_BLOCKED_GUEST');
            $mediaTitle = $card->filter('.media-title')->text();
            $this->assertNotSame('', $mediaTitle);
        }

       

        // total number of the media
        $mediaRepo = static::getContainer()->get(MediaRepository::class);
        $expectedMediaForActiveGuests = $mediaRepo->findForActiveGuests();
        $this->assertCount(count($expectedMediaForActiveGuests), $mediaCards);
    }


    public function testPortfolioPageFilterByAlbumByClickOnAlbumButtons():void
    {
        $client = static::createClient();
        $guestsRepo = static::getContainer()->get(UserRepository::class);
        $activeGuest = $guestsRepo->findOneBy(['email' => 'activeGuest@test.com']);
        
        //logged user 
        $client->loginUser($activeGuest);
        $crawler = $client->request('GET', '/portfolio');
        
        $firstAlbumLinkNode = $crawler->filter('.mb-5 a.btn[href^="/portfolio/"]')->first();
        $firstAlbumLink = $firstAlbumLinkNode->link();

        $crawler = $client->click($firstAlbumLink);
        $this->assertResponseIsSuccessful();

        $albumId = $client->getRequest()->attributes->get('id');

        $albumRepo = static::getContainer()->get(AlbumRepository::class);
        $album= $albumRepo->find($albumId);
        
        $activeAlbumText = $crawler->filter('.mb-5 a.btn.active')->text();
        $this->assertSame($album->getName(), $activeAlbumText);

        $mediaRepo = static::getContainer()->get(MediaRepository::class);
        $expectedMediaBelongToAlbum = $mediaRepo->findByAlbum($album);
        $this->assertCount(count($expectedMediaBelongToAlbum), $crawler->filter('.media'));

    }

}
