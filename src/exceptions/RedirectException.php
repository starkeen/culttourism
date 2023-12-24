<?php

declare(strict_types=1);

namespace app\exceptions;

class RedirectException extends LogicApplicationException
{
    private $target;

    /**
     * Исключение с перехватом в редирект
     *
     * @param string   $target
     * @param int|null $code
     */
    public function __construct(string $target, int $code = 301)
    {
        parent::__construct('redirect need', $code);

        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getTargetUrl(): string
    {
        return $this->target;
    }
}
