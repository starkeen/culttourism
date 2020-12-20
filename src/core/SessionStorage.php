<?php

declare(strict_types=1);

namespace app\core;

class SessionStorage
{
    /**
     * @param string $name
     * @return string|int|bool|null
     */
    public function getValue(string $name)
    {
        return $_SESSION[$name] ?? null;
    }

    /**
     * @param string $name
     * @param string|int|bool $value
     */
    public function setValue(string $name, $value): void
    {
        $_SESSION[$name] = $value;
    }
}
