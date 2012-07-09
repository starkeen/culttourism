<?php
function getAdminArea($smarty=null) {
    return $smarty->fetch(_DIR_TEMPLATES.'/_ajax/admin.sm.html');
}
?>