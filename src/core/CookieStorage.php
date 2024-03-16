<?php

declare(strict_types=1);

namespace app\core;

class CookieStorage
{
    /**
     * @param  string $name
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
     * @param int    $lifeTime
     */
    public function setCookie(string $name, string $value, int $lifeTime): void
    {
        setcookie(
            $name,
            $value,
            [
                'expires' => time() + $lifeTime, // Время жизни куки
                'path' => '/', // Путь, для которого кука будет доступна
                'domain' => '', // Домен куки
                'secure' => !GLOBAL_ERROR_REPORTING, // Устанавливать куку только по защищенному соединению
                'httponly' => !GLOBAL_ERROR_REPORTING, // Доступ к куке только через HTTP-протокол
                'samesite' => 'Strict', // Атрибут SameSite
            ],
        );
    }
}
