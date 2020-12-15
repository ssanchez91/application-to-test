<?php

namespace Tests\AppBundle\Security;

use AppBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use AppBundle\Security\GithubUserProvider;

class GithubUserProviderTest extends TestCase
{

    private $client;

    private $serializer;

    private $streamResponse;

    private $response;

    public function setUp()
    {
        $this->client = $this
            ->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->setMethods(['get'])  // Nous indiquons qu'une méthode va être redéfinie.
            ->getMock();

        $this->serializer = $this
            ->getMockBuilder('JMS\Serializer\Serializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->streamResponse = $this
            ->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this
            ->getMockBuilder('Psr\Http\Message\ResponseInterface')
            ->disableOriginalConstructor()
            ->getMock();    

    }


    public function testLoadUserByUsernameReturningAUser()
    {      
        
        $this->client->expects($this->once()) // Nous nous attendons à ce que la méthode get soit appelée une fois
            ->method('get')->willReturn($this->response);

        $this->response->expects($this->once()) // Nous nous attendons à ce que la méthode getBody soit appelée une fois
            ->method('getBody')->willReturn($this->streamResponse);

        $userData = ['login' => 'a login', 'name' => 'user name', 'email' => 'adress@mail.com', 'avatar_url' => 'url to the avatar', 'html_url' => 'url to profile'];
        $this->serializer->expects($this->once()) // Nous nous attendons à ce que la méthode deserialize soit appelée une fois
            ->method('deserialize')->willReturn($userData);

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $user  = $githubUserProvider->loadUserByUsername('token-valid');

        $expectedUser = new User($userData['login'], $userData['name'], $userData['email'], $userData['avatar_url'], $userData['html_url']);
        
        $this->assertEquals($expectedUser, $user);
        $this->assertEquals('AppBundle\Entity\User', get_class($user));
    }

    public function testLoadUserByUsernameReturningEmpty()
    {
        $this->client->expects($this->once()) // Nous nous attendons à ce que la méthode get soit appelée une fois
            ->method('get')->willReturn($this->response);
                   
        $this->response->expects($this->once()) // Nous nous attendons à ce que la méthode getBody soit appelée une fois
            ->method('getBody')->willReturn($this->streamResponse);

        $this->serializer->expects($this->once()) // Nous nous attendons à ce que la méthode deserialize soit appelée une fois
            ->method('deserialize')->willReturn([]);

        $this->expectException('LogicException');    

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $githubUserProvider->loadUserByUsername('token-valid');
    }

    public function tearDown()
    {
        $this->client = null;
        $this->serializer = null;
        $this->streamedResponse = null;
        $this->response = null;
    }
}