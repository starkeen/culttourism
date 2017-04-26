<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class BitlyTest extends TestCase
{
    /** @var Client|PHPUnit_Framework_MockObject_MockObject */
    private $guzzleClient;
    /** @var ResponseInterface|PHPUnit_Framework_MockObject_MockObject */
    private $guzzleResponse;
    /** @var StreamInterface|PHPUnit_Framework_MockObject_MockObject */
    private $guzzleResponseBody;

    public function setUp()
    {
        $this->guzzleClient = $this->getMockBuilder(Client::class)
                                   ->setMethods(['get'])
                                   ->getMock();
        $this->guzzleResponse = $this->getMockBuilder(ResponseInterface::class)
                                     ->getMock();
        $this->guzzleResponseBody = $this->getMockBuilder(StreamInterface::class)
                                         ->getMock();
    }

    public function testShorterNormal()
    {
        $expected = 'https://short.url';
        $answer = json_encode(
            [
                'status_code' => 200,
                'data' => [
                    'url' => $expected,
                ],
            ]
        );

        $this->guzzleResponseBody->expects($this->once())
                                 ->method('getContents')
                                 ->willReturn($answer);
        $this->guzzleResponse->expects($this->once())
                             ->method('getStatusCode')
                             ->willReturn(200);
        $this->guzzleResponse->expects($this->once())
                             ->method('getBody')
                             ->willReturn($this->guzzleResponseBody);
        $this->guzzleClient->expects($this->once())
                           ->method('get')
                           ->with(
                               'https://bit.ly/v3/shorten?access_token=[token]&longUrl=http%3A%2F%2Fhost.tld%2F&format=json'
                           )
                           ->willReturn($this->guzzleResponse);

        $bitly = new Bitly($this->guzzleClient);
        $bitly->setHost('https://bit.ly');
        $bitly->setToken('[token]');

        $result = $bitly->short('http://host.tld/');

        $this->assertEquals($expected, $result);
    }
}