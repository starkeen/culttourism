<?php

use app\constant\OgType;
use app\core\GlobalConfig;
use app\core\page\Content;
use app\core\page\Headers;
use app\core\SiteRequest;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\BaseApplicationException;
use app\exceptions\RedirectException;
use app\sys\TemplateEngine;
use app\utils\Urls;
use Psr\Log\LoggerInterface;

/**
 * Core - ядро
 */
abstract class Core
{
    /**
     * @var MyDB
     */
    protected $db;

    /**
     * @var SiteRequest
     */
    protected $siteRequest;

    /**
     * @var TemplateEngine
     */
    public $smarty;

    /**
     * @var Auth
     */
    public $auth;

    /**
     * @var WebUser
     */
    public $webUser;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var GlobalConfig
     */
    protected $globalConfig;

    /**
     * @var Content
     */
    public $pageContent;

    /**
     * @var Headers
     */
    public $pageHeaders;

    public $lastedit;
    public $lastedit_timestamp = 0;
    public $expiredate;

    /**
     * @param MyDB $db
     * @param SiteRequest $request
     */
    public function __construct(MyDB $db, SiteRequest $request)
    {
        $this->db = $db;
        $this->siteRequest = $request;
        $this->globalConfig = new GlobalConfig($this->db);
    }

    /**
     * Инициализация базовых элементов всех страниц
     * @throws RedirectException
     * @throws BaseApplicationException
     */
    public function init(): void
    {
        $this->auth->checkSession('web');

        $this->pageContent->getHead()->setTitleDelimiter($this->globalConfig->getTitleDelimiter());

        $this->pageContent->setJsResources($this->globalConfig->getJsResources());
        $this->pageContent->setUrlCss($this->globalConfig->getUrlCss());
        $this->pageContent->setUrlJs($this->globalConfig->getUrlJs());
        $this->pageContent->setUrlRss($this->globalConfig->getUrlRSS());

        if (!$this->globalConfig->isSiteActive()) {
            throw new BaseApplicationException();
        }

        $md = new MModules($this->db);
        $moduleData = $md->getModuleByURI($this->siteRequest->getModuleKey());

        $this->pageContent->getHead()->addOGMeta(OgType::TITLE(), $this->pageContent->getHead()->getTitle());
        $this->pageContent->getHead()->addOGMeta(OgType::DESCRIPTION(), $this->pageContent->getHead()->getDescription());
        $this->pageContent->getHead()->addOGMeta(OgType::SITE_NAME(), $this->globalConfig->getDefaultPageTitle());
        $this->pageContent->getHead()->addOGMeta(OgType::LOCALE(), 'ru_RU');
        $this->pageContent->getHead()->addOGMeta(OgType::TYPE(), 'website');
        $this->pageContent->getHead()->addOGMeta(OgType::URL(), Urls::getAbsoluteURL($_SERVER['REQUEST_URI']));
        $this->pageContent->getHead()->addOGMeta(OgType::IMAGE(), _SITE_URL . 'img/logo/culttourism-head.jpg');
        $this->pageContent->getHead()->addMicroData('image', _SITE_URL . 'img/logo/culttourism-head.jpg');
        if ($moduleData['md_photo_id']) {
            $ph = new MPhotos($this->db);
            $photo = $ph->getItemByPk($moduleData['md_photo_id']);
            $objImage = Urls::getAbsoluteURL($photo['ph_src']);
            $this->pageContent->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
            $this->pageContent->getHead()->addMicroData('image', $objImage);
        }

        if (!empty($moduleData)) {
            if ($moduleData['md_redirect'] !== null) {
                throw new RedirectException($moduleData['md_redirect']);
            }
            $this->pageContent->getHead()->addTitleElement($this->globalConfig->getDefaultPageTitle());
            if ($moduleData['md_title']) {
                $this->pageContent->getHead()->addTitleElement($moduleData['md_title']);
            }
            $this->pageContent->setH1($moduleData['md_title']);
            $this->pageContent->getHead()->addKeyword($this->globalConfig->getDefaultPageKeywords());
            $this->pageContent->getHead()->addKeyword($moduleData['md_keywords']);
            $this->pageContent->getHead()->addDescription($this->globalConfig->getDefaultPageDescription());
            $this->pageContent->getHead()->addDescription($moduleData['md_description']);

            $this->pageContent->getHead()->setCanonicalUrl('/' . $moduleData['md_url'] . '/');

            $this->pageContent->getHead()->addOGMeta(OgType::TITLE(), $this->globalConfig->getDefaultPageTitle());
            $this->pageContent->getHead()->addOGMeta(OgType::DESCRIPTION(), $this->globalConfig->getDefaultPageDescription());
            $this->pageContent->getHead()->addOGMeta(OgType::UPDATED_TIME(), $this->lastedit_timestamp);

            if ($moduleData['md_pagecontent'] !== null) {
                $this->pageContent->setBody($moduleData['md_pagecontent']);
            }

            $this->pageContent->getHead()->setRobotsIndexing($moduleData['md_robots']);
            $this->lastedit = $moduleData['md_timestamp'];
            $this->lastedit_timestamp = strtotime($moduleData['md_timestamp']);
            $this->expiredate = $moduleData['md_expiredate'];
        }
    }

    /**
     * Определение типа страницы внутри модуля и формирование контента
     */
    abstract public function compileContent(): void;
}
