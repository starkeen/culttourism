<?php

$dbau = $db->getTableName('authorizations');
$dbni = $db->getTableName('news_items');
$dbla = $db->getTableName('log_actions');
$dble = $db->getTableName('log_errors');

$db->sql = "DELETE FROM $dbau WHERE (au_date_expire < now() OR au_service = 'ajax')";
$db->exec();

$db->sql = "DELETE FROM $dbni WHERE DATEDIFF(now(), ni_pubdate) >= 3";
$db->exec();

$db->sql = "DELETE FROM $dbla WHERE DATEDIFF(now(), la_date) > 60";
$db->exec();

$db->sql = "DELETE FROM $dble WHERE DATEDIFF(now(), le_date) > 30";
$db->exec();

$db->sql = "OPTIMIZE TABLE $dbau, $dbni, $dbla, $dble";
$db->exec();
?>
