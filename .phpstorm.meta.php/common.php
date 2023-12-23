<?php

namespace PHPSTORM_META {
    override(\app\core\page\Content::getH1(), map(['' => '@']));
    override(\app\core\page\Content::getUrlRss(), map(['' => '@']));
    override(\app\core\page\Content::getYandexMapsKey(), map(['' => '@']));
    override(\app\core\page\Content::getUrlJs(), map(['' => '@']));
    override(\app\core\page\Content::getUrlCss(), map(['' => '@']));
    override(\app\core\page\Head::getMainMicroDataJSON(), map(['' => '@']));
    override(\app\core\page\Head::getWebsiteMicroDataJSON(), map(['' => '@']));
    override(\app\core\page\Head::getBreadcrumbsMicroDataJSON(), map(['' => '@']));
    override(\app\core\page\Head::getCustomMetas(), map(['' => '@']));
    override(\app\core\page\Head::getRobotsIndexing(), map(['' => '@']));
    override(\app\core\WebUser::isGuest(), map(['' => '@']));
}
