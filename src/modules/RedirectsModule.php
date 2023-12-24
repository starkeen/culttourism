<?php

declare(strict_types=1);

namespace app\modules;

use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\db\MyDB;
use app\exceptions\RedirectException;
use MRedirects;

class RedirectsModule implements ModuleInterface
{
    /**
     * @var MyDB
     */
    private $db;

    /**
     * @var MRedirects|null
     */
    private $redirectModel;

    /**
     * @var string|null
     */
    private $redirectToUrl;

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        $url = $request->getUrl();
        $redirects = $this->getRedirectsModel()->getActive();
        foreach ($redirects as $redirect) {
            $redirectUrl = preg_filter($redirect['rd_from'], $redirect['rd_to'], $url);
            if ($redirectUrl !== null) {
                $this->redirectToUrl = $redirectUrl;
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     * @throws     RedirectException
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        if ($this->redirectToUrl !== null) {
            throw new RedirectException($this->redirectToUrl);
        }
    }

    /**
     * @return MRedirects
     */
    private function getRedirectsModel(): MRedirects
    {
        if ($this->redirectModel === null) {
            $this->redirectModel = new MRedirects($this->db);
        }

        return $this->redirectModel;
    }

    /**
     * @param MRedirects $model
     */
    public function setRedirectsModel(MRedirects $model): void
    {
        $this->redirectModel = $model;
    }
}
