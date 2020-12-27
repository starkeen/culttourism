<?php

declare(strict_types=1);

namespace app\core;

class CookieStorage
{
    /**
     * @param string $name
     * @return string|null
     */
    public function getCookieValue(string $name): ?string
    {
        if (isset($_COOKIE[$name])) {
            return trim($_COOKIE[$name]);
        }

        return null;
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $lifeTime
     */
    public function setCookie(string $name, string $value, int $lifeTime): void
    {
        $secure = !_ER_REPORT;
        setcookie($name, $value, time() + $lifeTime, '/', '', $secure, $secure);
    }
}
