<?php

declare(strict_types=1);

namespace app\model\entity;

/**
 * @property int $ph_id
 * @property string $ph_link
 * @property string $ph_title
 * @property string $ph_author
 * @property string $ph_src
 * @property int $ph_weight
 * @property int $ph_width
 * @property int $ph_height
 * @property string $ph_mime
 * @property float $ph_lat
 * @property float $ph_lon
 * @property int $ph_pc_id
 * @property int $ph_pt_id
 * @property string $ph_date_add
 * @property int $ph_order
 * @property int $ph_active
 */
class Photo extends Entity
{
    /**
     * @param array $row
     */
    public function __construct(array $row)
    {
        $this->values = $row;
    }

    public function getId(): ?int
    {
        return $this->ph_id;
    }
}
