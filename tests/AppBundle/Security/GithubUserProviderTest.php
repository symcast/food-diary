<?php

namespace Tests\AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Security\GithubUserProvider;
use GuzzleHttp\Client;
use JMS\Serializer\Serializer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GithubUserProviderTest extends TestCase
{
    private $client;
    private $serializer;
    private $streamedResponse;
    private $response;

    public function setUp(): void
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->serializer = $this
            ->getMockBuilder( Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->streamedResponse = $this
            ->getMockBuilder(StreamInterface::class)
            ->getMock();

        $this->response = $this
            ->getMockBuilder( ResponseInterface::class)
            ->getMock();
    }

    public function testLoadUserByUsernameReturningAUser(): void
    {
        $this->client
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->response)
        ;

        $this->response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->streamedResponse);

        $userData = ['login' => 'a login', 'name' => 'user name', 'email' => 'adress@mail.com', 'avatar_url' => 'url to the avatar', 'html_url' => 'url to profile'];
        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn($userData);

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $user = $githubUserProvider->loadUserByUsername('an-access-token');


        $expectedUser = new User($userData['login'], $userData['name'], $userData['email'], $userData['avatar_url'], $userData['html_url']);
        $this->assertEquals($expectedUser, $user);
        $this->assertEquals('AppBundle\Entity\User', get_class($user));
    }

    public function testLoadUserByUsernameThrowingException(): void
    {
        $this->client
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->response)
        ;

        $this->response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->streamedResponse);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturn([]);

        $this->expectException('LogicException');

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $githubUserProvider->loadUserByUsername('an-access-token');
    }

    public function tearDown(): void
    {
        $this->client = null;
        $this->serializer = null;
        $this->streamedResponse = null;
        $this->response = null;
    }
}