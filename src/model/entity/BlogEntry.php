<?php

declare(strict_types=1);

namespace app\model\entity;

/**
 * @property int $br_id
 * @property string $br_date
 * @property string $br_title
 * @property string $br_url
 * @property string $br_text
 * @property string $br_picture
 * @property string $br_us_id
 * @property string $br_active
 */
class BlogEntry extends Entity
{
    /**
     * @var User|null
     */
    private $owner;

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
        return $this->br_id;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return strtotime($this->br_date);
    }

    /**
     * @return bool
     */
    public function isShown(): bool
    {
        return $this->isActive() && ($this->br_date < date('Y-m-d H:i:s'));
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->br_active === 1;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->br_text;
    }

    /**
     * @param User $user
     */
    public function setOwner(User $user): void
    {}

    /**
     * @return User|null
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * @return string
     */
    public function getRelativeLink(): string
    {
        $dateTime = strtotime($this->br_date);

        return '/blog/'
            . date('Y', $dateTime)
            . '/'
            . date('m', $dateTime)
            . '/'
            . ($this->br_url ?? date('d', $dateTime))
            . '.html';
    }

    /**
     * @return string
     */
    public function getHumanDate(): string
    {
        $dateTime = strtotime($this->br_date);

        return date('d.m.Y', $dateTime);
    }
}
