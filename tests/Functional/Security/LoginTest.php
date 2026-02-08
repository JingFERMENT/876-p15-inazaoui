<?php

namespace App\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    // test page rendered correctly
    public function testLoginPageShouldRender(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Connexion');
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_csrf_token"]');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    // test wrong username and password and blocked guest
    #[DataProvider('provideInvalidLogins')]
    public function testLoginFails(string $username, string $password, string $expectedErrorContains):void 
    {
        
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        
        $client->submitForm('Connexion', [ 
            '_username' => $username,
            '_password' => $password,
        ]);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();

        $this->assertAnySelectorTextContains('.alert-danger', $expectedErrorContains);
    }
    
    public static function provideInvalidLogins():iterable
    {
        yield 'wrong password' => [
            'activeGuest@test.com',
            'wrongPassword',
            'Identifiants invalides',
        ];

        yield 'unknown user' => [
            'unknownUser@test.com',
            'password123@',
            'Identifiants invalides',
        ];

        yield 'blocked user' => [
            'blockedGuest@test.com',
            'password123@',
            "Votre compte est bloqué ou non-activé. Veuillez contacter l'administrateur.",
        ];
    }

    
}
