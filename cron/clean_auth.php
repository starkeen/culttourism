<?php

$lifetime = 24 * 30;
$dba = $db->getTableName('authorizations');
$db->sql = "DELETE FROM $dba WHERE (au_date_login > DATE_ADD(now(), INTERVAL $lifetime HOUR) OR au_service = 'ajax')";
$db->exec();
?>