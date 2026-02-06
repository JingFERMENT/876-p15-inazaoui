<?php

namespace App\Tests\Functional\Front;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AboutPageTest extends WebTestCase
{
    public function testAboutPageIsSuccessfulAndShowContents(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/about');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h2.about-title', 'Qui suis-je ?');

        $this->assertSelectorExists('img.about-img[alt="Ina Zaoui"]');

        $imgSrc = $crawler->filter('img.about-img')->attr('src');

        $this->assertStringContainsString('/images/ina.png', $imgSrc);

        $this->assertSelectorTextContains('.about-description', "Chaque cliché d'Ina Zaoui est une ode à la beauté brute et à la fragilité de notre planète");
    }
}
