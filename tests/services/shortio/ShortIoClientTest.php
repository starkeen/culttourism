<?php

declare(strict_types=1);

namespace tests\services\shortio;

use app\services\shortio\ShortIoClient;
use app\services\shortio\ShortIoException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class ShortIoClientTest extends TestCase
{
    /**
     * @var (Client&MockObject)|MockObject|null
     */
    private Client|MockObject $guzzleMock;
    private ShortIoClient $client;

    public function setUp(): void
    {
        $this->guzzleMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['post'])
            ->getMock();

        $this->client = new ShortIoClient($this->guzzleMock, 'go.domain.net', 'token-example');
    }

    public function testShortProcessing(): void
    {
        $bodyMock = $this->getStreamInterfaceMock();
        $bodyMock->expects($this->once())
            ->method('getContents')
            ->willReturn('{"shortURL":"http://go.domain.net/aBcD"}');

        $responseMock = $this->getResponseInterfaceMock();
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyMock);

        $this->guzzleMock->expects($this->once())
            ->method('post')
            ->with(
                'https://api.short.io/links',
                [
                    'headers' => [
                        'Authorization' => 'token-example',
                        'accept' => 'application/json',
                        'content-type' => 'application/json',
                    ],
                    'json' => ['domain' => 'go.domain.net', 'originalURL' => 'http://localhost/my-link.html'],
                ]
            )
            ->willReturn($responseMock)
        ;

        $result = $this->client->short('http://localhost/my-link.html');

        $this->assertEquals('http://go.domain.net/aBcD', $result);
    }

    public function testProcessingServerError(): void
    {
        $request = $this->getRequestInterfaceMock();
        $response = $this->getResponseInterfaceMock();

        $this->guzzleMock->expects($this->once())
            ->method('post')
            ->willThrowException(new ClientException('Error example', $request, $response));

        $this->expectException(ShortIoException::class);
        $this->expectExceptionMessage('Short.io request error');
        $this->expectExceptionCode(500);

        $this->client->short('http://localhost/my.html');
    }

    public function testProcessingUnhandledError(): void
    {
        $this->guzzleMock->expects($this->once())
            ->method('post')
            ->willThrowException(new RuntimeException('Runtime error example'));

        $this->expectException(ShortIoException::class);
        $this->expectExceptionMessage('Runtime error example');
        $this->expectExceptionCode(0);

        $this->client->short('http://localhost/my.html');
    }

    private function getStreamInterfaceMock(): MockObject
    {
        return $this->getMockBuilder(StreamInterface::class)
            ->onlyMethods([
                'getContents',
                'close',
                'detach',
                'getSize',
                'tell',
                'eof',
                'isSeekable',
                'seek',
                'isReadable',
                'read',
                'isWritable',
                'write',
                'rewind',
                'getMetadata',
                '__toString',
            ])
            ->getMock();
    }

    private function getRequestInterfaceMock(): MockObject
    {
        return $this->getMockBuilder(RequestInterface::class)->getMock();
    }

    private function getResponseInterfaceMock(): MockObject
    {
        return $this->getMockBuilder(ResponseInterface::class)
            ->onlyMethods([
                'getBody',
                'getStatusCode',
                'getReasonPhrase',
                'getProtocolVersion',
                'getHeaders',
                'hasHeader',
                'getHeader',
                'getHeaderLine',
                'withBody',
                'withHeader',
                'withProtocolVersion',
                'withStatus',
                'withAddedHeader',
                'withoutHeader',
            ])
            ->getMock();
    }
}
