<?php

use app\includes\Bitly;
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
    /** @var MCurlCache|PHPUnit_Framework_MockObject_MockObject */
    private $curlCache;

    public function setUp()
    {
        $this->guzzleClient = $this->getMockBuilder(Client::class)
                                   ->setMethods(['get'])
                                   ->getMock();
        $this->guzzleResponse = $this->getMockBuilder(ResponseInterface::class)
                                     ->getMock();
        $this->guzzleResponseBody = $this->getMockBuilder(StreamInterface::class)
                                         ->getMock();
        $this->curlCache = $this->getMockBuilder(MCurlCache::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['get', 'put'])
                                ->getMock();
    }

    /**
     * Нормальный полный цикл без кэша
     */
    public function testShorterNormalWithoutCache()
    {
        $input = 'http://host.tld/';
        $expected = 'https://short.url';
        $requestUrl = 'https://bit.ly/v3/shorten?access_token=[token]&longUrl=http%3A%2F%2Fhost.tld%2F&format=json';
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
                           ->with($requestUrl)
                           ->willReturn($this->guzzleResponse);
        $this->curlCache->expects($this->once())
                        ->method('get')
                        ->with($requestUrl)
                        ->willReturn(null);
        $this->curlCache->expects($this->once())
                        ->method('put')
                        ->with($requestUrl, $answer, Bitly::CURL_CACHE_TTL);

        $bitly = new Bitly($this->guzzleClient, $this->curlCache);
        $bitly->setHost('https://bit.ly');
        $bitly->setToken('[token]');

        $result = $bitly->short($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Нормальный полный цикл с кэшем
     */
    public function testShorterNormalWithCache()
    {
        $input = 'http://host.tld/';
        $expected = 'https://short.url';
        $requestUrl = 'https://bit.ly/v3/shorten?access_token=[token]&longUrl=http%3A%2F%2Fhost.tld%2F&format=json';
        $answer = json_encode(
            [
                'status_code' => 200,
                'data' => [
                    'url' => $expected,
                ],
            ]
        );

        $this->guzzleResponseBody->expects($this->never())
                                 ->method('getContents');
        $this->guzzleResponse->expects($this->never())
                             ->method('getStatusCode');
        $this->guzzleResponse->expects($this->never())
                             ->method('getBody');
        $this->guzzleClient->expects($this->never())
                           ->method('get');
        $this->curlCache->expects($this->once())
                        ->method('get')
                        ->with($requestUrl)
                        ->willReturn($answer);
        $this->curlCache->expects($this->never())
                        ->method('put');

        $bitly = new Bitly($this->guzzleClient, $this->curlCache);
        $bitly->setHost('https://bit.ly');
        $bitly->setToken('[token]');

        $result = $bitly->short($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * В теле ответа код с ошибкой
     */
    public function testShorterErrorStatusCode()
    {
        $input = 'http://host.tld/';
        $expected = $input;
        $answer = json_encode(
            [
                'status_code' => 100500,
                'data' => [
                    'url' => 'undefined',
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

        $bitly = new Bitly($this->guzzleClient, $this->curlCache);
        $bitly->setHost('https://bit.ly');
        $bitly->setToken('[token]');

        $result = $bitly->short($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * В заголовке ответа код с ошибкой
     */
    public function testShorterErrorHeaderCode()
    {
        $input = 'http://host.tld/';
        $expected = $input;

        $this->guzzleResponseBody->expects($this->never())
                                 ->method('getContents');
        $this->guzzleResponse->expects($this->once())
                             ->method('getStatusCode')
                             ->willReturn(100500);
        $this->guzzleResponse->expects($this->never())
                             ->method('getBody');
        $this->guzzleClient->expects($this->once())
                           ->method('get')
                           ->with(
                               'https://bit.ly/v3/shorten?access_token=[token]&longUrl=http%3A%2F%2Fhost.tld%2F&format=json'
                           )
                           ->willReturn($this->guzzleResponse);

        $bitly = new Bitly($this->guzzleClient, $this->curlCache);
        $bitly->setHost('https://bit.ly');
        $bitly->setToken('[token]');

        $result = $bitly->short($input);

        $this->assertEquals($expected, $result);
    }
}