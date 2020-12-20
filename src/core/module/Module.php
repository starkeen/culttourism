<?php

declare(strict_types=1);

namespace app\core\module;

use app\constant\OgType;
use app\core\GlobalConfig;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\RedirectException;
use app\sys\TemplateEngine;
use app\utils\Urls;
use MModules;
use MPhotos;

abstract class Module
{
    /**
     * @var MyDB
     */
    protected $db;

    /**
     * @var TemplateEngine
     */
    protected $templateEngine;

    /**
     * @var WebUser
     */
    protected $webUser;

    /**
     * @var GlobalConfig
     */
    protected $globalConfig;

    public function __construct(MyDB $db, TemplateEngine $templateEngine, WebUser $webUser, GlobalConfig $globalConfig)
    {
        $this->db = $db;
        $this->templateEngine = $templateEngine;
        $this->webUser = $webUser;
        $this->globalConfig = $globalConfig;
    }

    /**
     * Обработка запроса
     * @param SiteRequest  $request
     * @param SiteResponse $response
     */
    abstract protected function process(SiteRequest $request, SiteResponse $response): void;

    /**
     * @return string
     */
    abstract protected function getModuleKey(): string;

    /**
     * @param SiteResponse $response
     * @throws RedirectException
     */
    protected function preProcess(SiteResponse $response): void
    {
        $this->webUser->getAuth()->checkSession('web');

        $md = new MModules($this->db);
        $moduleData = $md->getModuleByURI($this->getModuleKey());

        $response->getContent()->getHead()->addOGMeta(OgType::SITE_NAME(), $this->globalConfig->getDefaultPageTitle());
        $response->getContent()->getHead()->addOGMeta(OgType::LOCALE(), 'ru_RU');
        $response->getContent()->getHead()->addOGMeta(OgType::TYPE(), 'website');
        $response->getContent()->getHead()->addOGMeta(OgType::URL(), Urls::getAbsoluteURL($_SERVER['REQUEST_URI']));
        $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), _SITE_URL . 'img/logo/culttourism-head.jpg');
        $response->getContent()->getHead()->addMicroData('image', _SITE_URL . 'img/logo/culttourism-head.jpg');
        if ($moduleData['md_photo_id']) {
            $ph = new MPhotos($this->db);
            $photo = $ph->getItemByPk($moduleData['md_photo_id']);
            $objImage = Urls::getAbsoluteURL($photo['ph_src']);
            $response->getContent()->getHead()->addOGMeta(OgType::IMAGE(), $objImage);
            $response->getContent()->getHead()->addMicroData('image', $objImage);
        }

        if (!empty($moduleData)) {
            if ($moduleData['md_redirect'] !== null) {
                throw new RedirectException($moduleData['md_redirect']);
            }
            $response->getContent()->getHead()->addTitleElement($this->globalConfig->getDefaultPageTitle());
            if ($moduleData['md_title']) {
                $response->getContent()->getHead()->addTitleElement($moduleData['md_title']);
            }
            $response->getContent()->setH1($moduleData['md_title']);
            $response->getContent()->getHead()->addKeyword($this->globalConfig->getDefaultPageKeywords());
            $response->getContent()->getHead()->addKeyword($moduleData['md_keywords']);
            $response->getContent()->getHead()->addDescription($this->globalConfig->getDefaultPageDescription());
            $response->getContent()->getHead()->addDescription($moduleData['md_description']);

            $response->getContent()->getHead()->setCanonicalUrl('/' . $moduleData['md_url'] . '/');

            $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $this->globalConfig->getDefaultPageTitle());
            $response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $this->globalConfig->getDefaultPageDescription());
            if ($response->getLastEditTimestamp() !== null) {
                $response->getContent()->getHead()->addOGMeta(OgType::UPDATED_TIME(), (string) $response->getLastEditTimestamp());
            }

            if ($moduleData['md_pagecontent'] !== null) {
                $response->getContent()->setBody($moduleData['md_pagecontent']);
            }

            $response->getContent()->getHead()->setRobotsIndexing($moduleData['md_robots']);
            $response->setLastEditTimestamp(strtotime($moduleData['md_lastedit']));
        }
    }

    /**
     * @param SiteResponse $response
     */
    protected function postProcess(SiteResponse $response): void
    {
        $response->getContent()->getHead()->addOGMeta(OgType::TITLE(), $response->getContent()->getHead()->getTitle());
        $response->getContent()->getHead()->addOGMeta(OgType::DESCRIPTION(), $response->getContent()->getHead()->getDescription());
    }

    /**
     * Обработка запроса
     * @param SiteRequest $request
     * @param SiteResponse $response
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        $this->preProcess($response);
        $this->process($request, $response);
        $this->postProcess($response);
    }
}
