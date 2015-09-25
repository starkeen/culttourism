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
$ni->optimize();


$cd = new MCityData($db);
$cd->optimize();
$pp = new MPagePoints($db);
$pp->optimize();
$pc = new MPageCities($db);
$pc->optimize();
$sp = new MSysProperties($db);
$sp->optimize();
$ws = new MWordstat($db);
$ws->optimize();
