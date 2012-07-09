<?php

$lifetime = 24 * 30;
$dba = $db->getTableName('authorizations');
$db->sql = "DELETE FROM $dba WHERE (au_date_expire < now() OR au_service = 'ajax')";
$db->exec();
?>