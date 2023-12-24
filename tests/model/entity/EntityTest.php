<?php

declare(strict_types=1);

namespace tests\model\entity;

use app\model\entity\Entity;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    /**
     * @var Entity
     */
    private $entity;

    protected function setUp(): void
    {
        /**
         * @property string $field_string
         */
        $this->entity = new class () extends Entity {
            public function getId(): ?int
            {
                return 12345;
            }
        };
    }

    public function testFields(): void
    {
        $this->entity->field_string = 'string';
        $this->entity->field_integer = 54321;
        $this->entity->field_bool = true;
        $this->entity->field_null = null;

        self::assertEquals('string', $this->entity->field_string);
        self::assertEquals(54321, $this->entity->field_integer);
        self::assertTrue($this->entity->field_bool);
        self::assertNull($this->entity->field_null);

        self::assertTrue(isset($this->entity->field_string));
        self::assertFalse(isset($this->entity->field_undefined));

        self::assertEquals(12345, $this->entity->getId());

        self::assertEquals(
            ['field_string', 'field_integer', 'field_bool', 'field_null'],
            $this->entity->getModifiedFields()
        );
    }

    public function testNow(): void
    {
        self::assertEquals(date('Y-m-d H:i:s'), $this->entity->now());
    }
}
