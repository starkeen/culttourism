<?php

declare(strict_types=1);

namespace tests\constant;

use app\constant\MimeType;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class MimeTypeTest extends TestCase
{
    /**
     * @param string $mime
     * @param string $extension
     * @dataProvider getKnownTypes
     */
    public function testExistedTypes(string $mime, string $extension): void
    {
        $mimeType = new MimeType($mime);

        self::assertEquals($extension, $mimeType->getDefaultExtension());
    }

    /**
     * @return array[]
     */
    public function getKnownTypes(): array
    {
        return [
            'jpeg' => ['image/jpeg', 'jpg'],
            'png' => ['image/png', 'png'],
        ];
    }

    public function testUnknownType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $mimeType = new MimeType('unknown');
    }
}
