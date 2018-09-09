<?php

$cc = new MCurlCache($db);
$cc->cleanExpired();

$au = new MAuthorizations($db);
$au->cleanExpired();
$au->cleanUnused();

$la = new MLogActions($db);
$la->cleanExpired();

$le = new MLogErrors($db);
$le->cleanExpired();

$ni = new MNewsItems($db);
$ni->cleanExpired();
