<?php

declare(strict_types=1);

namespace app\model\entity;

/**
 * @property int $pc_id
 * @property string $pc_title
 * @property string $pc_title_unique
 * @property string $pc_text
 * @property string|null $pc_announcement
 * @property string|null $pc_keywords
 * @property string $pc_description
 * @property int $pc_url_id
 * @property int $pc_city_id
 * @property int $pc_region_id
 * @property int $pc_country_id
 * @property int $pc_order
 * @property int $pc_active
 */
class City extends Entity
{
    public function getId(): ?int
    {
        return $this->pc_id;
    }
}
