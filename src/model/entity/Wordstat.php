<?php

declare(strict_types=1);

namespace app\model\entity;

/**
 * @property int $ws_id
 * @property int $ws_city_id
 * @property string $ws_city_title
 * @property int $ws_rep_id
 * @property int $ws_weight
 * @property string $ws_weight_date
 * @property int $ws_weight_max
 * @property string $ws_weight_max_date
 * @property int $ws_weight_min
 * @property string $ws_weight_min_date
 * @property int $ws_position
 * @property string $ws_position_date
 * @property int $ws_position_last
 */
class Wordstat extends Entity
{
    /**
     * @param array $row
     */
    public function __construct(array $row)
    {
        $this->values = $row;
    }
}
