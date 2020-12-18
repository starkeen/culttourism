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
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     * @throws RedirectException
     */
    public function process(SiteRequest $request, SiteResponse $response): void
    {
        $url = $request->getUrl();
        $redirectModel = new MRedirects($this->db);
        $redirects = $redirectModel->getActive();
        foreach ($redirects as $redirect) {
            $redirectUrl = preg_filter($redirect['rd_from'], $redirect['rd_to'], $url);
            if ($redirectUrl !== null) {
                throw new RedirectException($redirectUrl);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return true;
    }
}
