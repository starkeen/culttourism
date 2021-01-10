<?php

declare(strict_types=1);

namespace app\model\constant;

use MyCLabs\Enum\Enum;

/**
 * @method static self MONUMENT()
 * @method static self INTERESTING()
 * @method static self CHURCH()
 * @method static self MUSEUM()
 * @method static self NATURE()
 * @method static self CASTLE()
 * @method static self STATION()
 * @method static self RESTAURANT()
 * @method static self HOTEL()
 */
class PointType extends Enum
{
    public const MONUMENT = 1;
    public const INTERESTING = 2;
    public const CHURCH = 3;
    public const MUSEUM = 4;
    public const NATURE = 5;
    public const CASTLE = 6;
    public const STATION = 7;
    public const RESTAURANT = 8;
    public const HOTEL = 9;

    private static array $sights = [
        self::MONUMENT,
        self::INTERESTING,
        self::CHURCH,
        self::MUSEUM,
        self::NATURE,
        self::CASTLE,
    ];

    private static array $fullName = [
        self::MONUMENT => 'памятники',
        self::INTERESTING => 'интересные места',
        self::CHURCH => 'церкви и монастыри',
        self::MUSEUM => 'музеи, выставки, галереи',
        self::NATURE => 'природные и ландшафтные памятники',
        self::CASTLE => 'усадьбы, дворцы и замки',
        self::STATION => 'вокзалы и аэропорты',
        self::RESTAURANT => 'кафе, столовые, рестораны',
        self::HOTEL => 'гостиницы, мотели, хостелы',
    ];

    private static array $shortName = [
        self::MONUMENT => 'памятники',
        self::INTERESTING => 'интересное',
        self::CHURCH => 'религия',
        self::MUSEUM => 'музеи',
        self::NATURE => 'парки',
        self::CASTLE => 'архитектура',
        self::STATION => 'вокзалы',
        self::RESTAURANT => 'кафе',
        self::HOTEL => 'гостиницы',
    ];

    private static array $icon = [
        self::MONUMENT => 'statue.png',
        self::INTERESTING => 'camera.png',
        self::CHURCH => 'religion.png',
        self::MUSEUM => 'museum.png',
        self::NATURE => 'park.png',
        self::CASTLE => 'arch.png',
        self::STATION => 'station.png',
        self::RESTAURANT => 'cafe.png',
        self::HOTEL => 'hotel.png',
    ];

    /**
     * Типы, относящиеся к достопримечательностям
     * @return self[]
     */
    public static function getSights(): array
    {
        $result = [];
        foreach (self::values() as $value) {
            if (in_array($value->getValue(), self::$sights, true)) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Сервисные типы точек
     * @return self[]
     */
    public static function getServices(): array
    {
        $result = [];
        foreach (self::values() as $value) {
            if (!in_array($value->getValue(), self::$sights, true)) {
                $result[] = $value;
            }
        }

        return $result;
    }

    public function getIcon(): string
    {
        return self::$icon[$this->getValue()];
    }

    public function getFullName(): string
    {
        return self::$fullName[$this->getValue()];
    }

    public function getShortName(): string
    {
        return self::$shortName[$this->getValue()];
    }

    public function isSight(): bool
    {
        return in_array($this->getValue(), self::$sights, true);
    }
}
