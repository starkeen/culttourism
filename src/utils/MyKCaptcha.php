<?php

declare(strict_types=1);

namespace app\utils;

use KCAPTCHA\KCAPTCHA;

class MyKCaptcha extends KCAPTCHA
{
    public const SESSION_KEY = 'captcha_keystring';
}
