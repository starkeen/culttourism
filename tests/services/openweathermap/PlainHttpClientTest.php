<?php

declare(strict_types=1);

namespace tests\services\openweathermap;

use app\services\openweathermap\PlainHttpClient;
use app\services\openweathermap\WeatherData;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class PlainHttpClientTest extends TestCase
{
    private const URL = 'protocol://host/url?data=value';

    public function testSuccessRequest(): void
    {
        $guzzleMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['get'])
            ->getMock();
        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $responseBodyMock = $this->getMockBuilder(StreamInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getContents'])
            ->getMockForAbstractClass();

        $client = new PlainHttpClient($guzzleMock);

        $responseMock->expects(self::once())->method('getStatusCode')->willReturn(200);
        $responseMock->expects(self::once())->method('getBody')->willReturn($responseBodyMock);
        $responseBodyMock->expects(self::once())->method('getContents')->willReturn('[]');

        $guzzleMock->expects(self::once())->method('get')->with(self::URL)->willReturn($responseMock);

        $result = $client->fetchData(self::URL);

        self::assertInstanceOf(WeatherData::class, $result);
    }

    public function testRequestWithBadCode(): void
    {
        $guzzleMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['get'])
            ->getMock();
        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatusCode', 'getBody'])
            ->getMock();

        $client = new PlainHttpClient($guzzleMock);

        $responseMock->expects(self::once())->method('getStatusCode')->willReturn(500);
        $responseMock->expects(self::never())->method('getBody');

        $guzzleMock->expects(self::once())->method('get')->with(self::URL)->willReturn($responseMock);

        $result = $client->fetchData(self::URL);

        self::assertNull($result);
    }
}
