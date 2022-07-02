<?php

declare(strict_types=1);

namespace app\model\entity;

use app\model\constant\PointType;

/**
 * @property int $pt_id
 * @property string $pt_name
 * @property string $pt_description
 * @property string $pt_slugline
 * @property int $pt_citypage_id
 * @property float $pt_latitude
 * @property float $pt_longitude
 * @property int $pt_latlon_zoom
 * @property int $pt_type_id
 * @property string $pt_create_date
 * @property int $pt_create_user
 * @property string $pt_lastup_date
 * @property int $pt_lastup_user
 * @property int $pt_city_id
 * @property int $pt_region_id
 * @property int $pt_country_id
 * @property string|null $pt_website
 * @property string|null $pt_worktime
 * @property string|null $pt_adress
 * @property string|null $pt_phone
 * @property string|null $pt_email
 * @property int $pt_photo_id
 * @property int $pt_cnt_shows
 * @property int $pt_rank
 * @property int|null $pt_order
 * @property int $pt_is_best
 * @property int $pt_deleted_at
 */
class Point extends Entity
{
    /**
     * @param array $row
     */
    public function __construct(array $row)
    {
        $this->values = $row;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->pt_id;
    }

    public function getType(): ?PointType
    {
        if ($this->pt_type_id !== 0) {
            return new PointType($this->pt_type_id);
        }

        return null;
    }
}
