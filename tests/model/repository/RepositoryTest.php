<?php

declare(strict_types=1);

namespace tests\model\repository;

use app\db\MyDB;
use app\model\entity\Entity;
use app\model\repository\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    protected $repository;

    /**
     * @var MyDB|MockObject
     */
    private $dbMock;

    protected function setUp(): void
    {
        $this->dbMock = $this->getMockBuilder(MyDB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute', 'setSQL'])
            ->getMock();

        $this->repository = new class ($this->dbMock) extends Repository {
            protected static function getTableName(): string
            {
                return 'table_name';
            }

            protected static function getPkName(): string
            {
                return 'primary_key_id';
            }
        };
    }

    public function testSaveExistedEntity(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getModifiedFields', '__get'])
            ->getMock();

        $entity->expects(self::exactly(2))->method('getId')->willReturn(122345);
        $entity->expects(self::exactly(3))->method('__get')->willReturnMap([
            ['field_one', 'value_one'],
            ['field_two', 54321],
            ['field_3', null],
        ]);
        $entity->expects(self::once())->method('getModifiedFields')->willReturn(['field_one', 'field_two', 'field_3']);

        $this->dbMock->expects(self::once())
            ->method('setSQL')
            ->with('UPDATE `table_name` SET '
                . PHP_EOL
                . 'field_one = :field_one,'
                . PHP_EOL
                . 'field_two = :field_two,'
                . PHP_EOL
                . 'field_3 = :field_3'
                . PHP_EOL
                . 'WHERE primary_key_id = :primary_key_id');
        $this->dbMock->expects(self::once())
            ->method('execute')
            ->with(
                [
                    ':primary_key_id' => 122345,
                    ':field_one' => 'value_one',
                    ':field_two' => 54321,
                    ':field_3' => null,
                ]
            );

        $this->repository->save($entity);
    }

    public function testSaveNewEntity(): void
    {
        $entity = $this->getMockBuilder(Entity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getModifiedFields', '__get'])
            ->getMock();

        $entity->expects(self::once())->method('getId')->willReturn(null);
        $entity->expects(self::exactly(3))->method('__get')->willReturnMap([
            ['field_one', 'value_one'],
            ['field_two', 54321],
            ['field_3', null],
        ]);
        $entity->expects(self::once())->method('getModifiedFields')->willReturn(['field_one', 'field_two', 'field_3']);

        $this->dbMock->expects(self::once())
            ->method('setSQL')
            ->with('INSERT INTO `table_name` SET '
                . PHP_EOL
                . 'field_one = :field_one,'
                . PHP_EOL
                . 'field_two = :field_two,'
                . PHP_EOL
                . 'field_3 = :field_3'
                . PHP_EOL);
        $this->dbMock->expects(self::once())
            ->method('execute')
            ->with(
                [
                    ':field_one' => 'value_one',
                    ':field_two' => 54321,
                    ':field_3' => null,
                ]
            );

        $this->repository->save($entity);
    }
}
