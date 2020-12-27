<?php

declare(strict_types=1);

namespace tests\db;

use app\db\MyDB;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MyDBTest extends TestCase
{
    public function testTableNameWithoutPrefix(): void
    {
        $db = new MyDB('host', 'user', 'pass', 'database');

        self::assertEquals('`table_name`', $db->getTableName('table_name'));
    }

    public function testTableNameWithtPrefix(): void
    {
        $db = new MyDB('host', 'user', 'pass', 'database', 'pref');

        self::assertEquals('`pref_table_name`', $db->getTableName('table_name'));
    }

    public function testStringEscaping(): void
    {
        $db = new MyDB('host', 'user', 'pass', 'database');

        $pdoMock = $this->getPDOMock();
        $db->setPDO($pdoMock);

        $pdoMock->expects(self::once())->method('quote')->with('example_string')->willReturn('quoted_string');

        self::assertEquals('quoted_string', $db->getEscapedString('example_string'));
    }

    /**
     * @return MockObject|PDO
     */
    private function getPDOMock(): MockObject
    {
        return $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'quote',
            ])
            ->getMock();
    }
}
