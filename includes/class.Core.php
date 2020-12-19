<?php

use app\constant\OgType;
use app\core\GlobalConfig;
use app\core\page\Content;
use app\core\page\Headers;
use app\core\SiteRequest;
use app\core\SiteResponse;
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
    public $templateEngine;

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
     * @var SiteResponse
     */
    public $response;

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
        $this->webUser->getAuth()->checkSession('web');

        $this->response->getContent()->getHead()->setTitleDelimiter($this->globalConfig->getTitleDelimiter());

        $this->response->getContent()->setJsResources($this->globalConfig->getJsResources());
        $this->response->getContent()->setUrlCss($this->globalConfig->getUrlCss());
        $this->response->getContent()->setUrlJs($this->globalConfig->getUrlJs());
        $this->response->getContent()->setUrlRss($this->globalConfig->getUrlRSS());

        if (!$this->globalConfig->isSiteActive()) {
            throw new BaseApplicationException();
        }

        $md = new MModules($this->db);
        $moduleData = $md->getModuleByURI($this->siteRequest->getModuleKey());

        $this->response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $this->response->getContent()->getHead()->getTitle());
        $this->response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $this->response->getContent()->getHead()->getDescription());
        $this->response->getContent()->getHead()->addOGMeta(OgType::SITE_NAME(), $this->globalConfig->getDefaultPageTitle());
        $this->response->getContent()->getHead()->addOGMeta(OgType::LOCALE(), 'ru_RU');
        $this->response->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'website');
        $this->response->getContent()->getHead()->addOGMeta(OgType::URL(), Urls::getAbsoluteURL($_SERVER['REQUEST_URI']));
        $this->response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), _SITE_URL . 'img/logo/culttourism-head.jpg');
        $this->response->getContent()->getHead()->addMicroData('image', _SITE_URL . 'img/logo/culttourism-head.jpg');
        if ($moduleData['md_photo_id']) {
            $ph = new MPhotos($this->db);
            $photo = $ph->getItemByPk($moduleData['md_photo_id']);
            $objImage = Urls::getAbsoluteURL($photo['ph_src']);
            $this->response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
            $this->response->getContent()->getHead()->addMicroData('image', $objImage);
        }

        if (!empty($moduleData)) {
            if ($moduleData['md_redirect'] !== null) {
                throw new RedirectException($moduleData['md_redirect']);
            }
            $this->response->getContent()->getHead()->addTitleElement($this->globalConfig->getDefaultPageTitle());
            if ($moduleData['md_title']) {
                $this->response->getContent()->getHead()->addTitleElement($moduleData['md_title']);
            }
            $this->response->getContent()->setH1($moduleData['md_title']);
            $this->response->getContent()->getHead()->addKeyword($this->globalConfig->getDefaultPageKeywords());
            $this->response->getContent()->getHead()->addKeyword($moduleData['md_keywords']);
            $this->response->getContent()->getHead()->addDescription($this->globalConfig->getDefaultPageDescription());
            $this->response->getContent()->getHead()->addDescription($moduleData['md_description']);

            $this->response->getContent()->getHead()->setCanonicalUrl('/' . $moduleData['md_url'] . '/');

            $this->response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $this->globalConfig->getDefaultPageTitle());
            $this->response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $this->globalConfig->getDefaultPageDescription());
            if ($this->response->getLastEditTimestamp() !== null) {
                $this->response->getContent()->getHead()->addOGMeta(OgType::UPDATED_TIME(), $this->response->getLastEditTimestamp());
            }

            if ($moduleData['md_pagecontent'] !== null) {
                $this->response->getContent()->setBody($moduleData['md_pagecontent']);
            }

            $this->response->getContent()->getHead()->setRobotsIndexing($moduleData['md_robots']);
            $this->response->setLastEditTimestamp(strtotime($moduleData['md_lastedit']));
        }
    }

    /**
     * Определение типа страницы внутри модуля и формирование контента
     */
    abstract public function compileContent(): void;
}
