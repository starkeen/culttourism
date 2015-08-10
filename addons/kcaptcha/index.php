<?php

error_reporting(E_ALL & ~E_DEPRECATED);

include('kcaptcha.php');

session_start();

$captcha = new KCAPTCHA();

if ($_REQUEST[session_name()]) {
    $_SESSION['captcha_keystring'] = $captcha->getKeyString();
}
