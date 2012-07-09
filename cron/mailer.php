<?php
//echo '<p>mailer... ';
include(_DIR_INCLUDES.'/class.Mailing.php');
$cnt = Mailing::sendFromPool(10);
//echo 'ok=' . $cnt;
?>
