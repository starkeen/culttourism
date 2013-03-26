<?php

$dba = $db->getTableName('authorizations');
$dbni = $db->getTableName('news_items');

$db->sql = "DELETE FROM $dba WHERE (au_date_expire < now() OR au_service = 'ajax')";
$db->exec();

$db->sql = "DELETE FROM $dbni WHERE DATEDIFF(now(), ni_pubdate) >= 3";
$db->exec();

$db->sql = "OPTIMIZE TABLE $dba, $dbni";
$db->exec();
?>
