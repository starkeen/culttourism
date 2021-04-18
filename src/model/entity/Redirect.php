<?php

declare(strict_types=1);

namespace app\model\entity;

/**
 * @property int $rd_id
 * @property string $rd_from
 * @property string $rd_to
 * @property string $rd_description
 * @property int $rd_order
 * @property int $rd_active
 */
class Redirect extends Entity
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
        return $this->rd_id ?? null;
    }

    public function beforeSave(): void
    {
        if ($this->getId() === null) {
            // при первом сохранении исправляем формат и добавляем экранирование
            if (strpos($this->rd_from, GLOBAL_SITE_URL) === 0) {
                $this->rd_from = str_replace(GLOBAL_SITE_URL, '/', $this->rd_from);
            }

            $this->rd_from = preg_quote($this->rd_from, '/') . '/i';
            $this->rd_order = 10;
            $this->rd_active = 1;
        }
    }
}
