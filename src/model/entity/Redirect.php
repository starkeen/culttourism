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
    private const REGEXP_DELIMITER = '/';
    private const REGEXP_MODIFIER = 'i';

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
        $this->rd_from = trim($this->rd_from);
        $this->rd_to = trim($this->rd_to);

        if ($this->getId() === null) {
            // при первом сохранении исправляем формат и добавляем экранирование
            if (strpos($this->rd_from, GLOBAL_SITE_URL) === 0) {
                $this->rd_from = str_replace(GLOBAL_SITE_URL, '/', $this->rd_from);
                $this->rd_to = str_replace(GLOBAL_SITE_URL, '/', $this->rd_to);
            }

            $this->rd_from = self::REGEXP_DELIMITER
                . preg_quote($this->rd_from, self::REGEXP_DELIMITER)
                . self::REGEXP_DELIMITER . self::REGEXP_MODIFIER;
            $this->rd_order = 10;
            $this->rd_active = 1;
        }
    }
}
