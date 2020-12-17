<?php

namespace PHPSTORM_META {
    override(\app\core\page\Content::getH1(), map(['' => '@']));
    override(\app\core\page\Content::getUrlRss(), map(['' => '@']));
    override(\app\core\page\Content::getUrlJs(), map(['' => '@']));
    override(\app\core\page\Content::getUrlCss(), map(['' => '@']));
    override(\app\core\page\Head::getMicroDataJSON(), map(['' => '@']));
    override(\app\core\page\Head::getCustomMetas(), map(['' => '@']));
}
