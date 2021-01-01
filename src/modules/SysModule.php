<?php

declare(strict_types=1);

namespace app\modules;

use app\core\assets\AssetsServiceBuilder;
use app\core\assets\constant\Pack;
use app\core\assets\constant\Type;
use app\core\module\Module;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\GlobalConfig;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\exceptions\RedirectException;
use app\sys\DeployBitbucket;
use app\sys\Logger;
use app\sys\TemplateEngine;
use GuzzleHttp\Client;
use Mailing;
use MSysProperties;

class SysModule extends Module implements ModuleInterface
{
    private const SETTINGS_BRANCH_DEPLOY = 9;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param MyDB $db
     * @param TemplateEngine $templateEngine
     * @param WebUser $webUser
     * @param GlobalConfig $globalConfig
     * @param Logger $logger
     */
    public function __construct(
        MyDB $db,
        TemplateEngine $templateEngine,
        WebUser $webUser,
        GlobalConfig $globalConfig,
        Logger $logger
    ) {
        parent::__construct($db, $templateEngine, $webUser, $globalConfig);
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     * @throws NotFoundException
     * @throws RedirectException
     */
    protected function process(SiteRequest $request, SiteResponse $response): void
    {
        if ($request->getLevel2() !== null) {
            throw new NotFoundException();
        }

        if ($request->getLevel1() === null && empty($request->getGET())) {
            throw new RedirectException('/');
        } elseif ($request->getLevel1() === 'bitbucket' && $request->getGETParam('key') !== null) {
            $this->getBitbucket(trim($_GET['key']));
        } elseif ($request->getLevel1() === 'static' && $request->getGETParam('type') !== null && $request->getGETParam('pack') !== null) {
            $typeName = trim($request->getGETParam('type'));
            $packName = trim($request->getGETParam('pack'));
            $type = new Type($typeName);
            $pack = new Pack($packName);
            $this->getStatic($type, $pack);
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getModuleKey(): string
    {
        return 'sys';
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === $this->getModuleKey();
    }

    /**
     * @param Type $type
     * @param Pack $pack
     */
    private function getStatic(Type $type, Pack $pack): void
    {
        header('Content-Type: ' . $type->getContentType());

        echo AssetsServiceBuilder::build()->getFull($type, $pack);
        exit();
    }

    /**
     * @param string|null $key
     * @throws NotFoundException
     */
    private function getBitbucket(string $key = null): void
    {
        if (isset($_POST) && !empty($_POST)) {
            $this->logger->info('Запрос на деплой', $_POST);
            $req = json_decode($_POST['payload']);

            $sp = new MSysProperties($this->db);
            $config = $sp->getSettingsByBranchId(self::SETTINGS_BRANCH_DEPLOY);

            if ($key && $key === $config['git_key']) {
                $config['location'] = GLOBAL_DIR_ROOT . '/';

                $bb = $this->getBitbucketDeployHelper($config);
                $res = $bb->deploy($req);

                if (!empty($res)) {
                    $this->templateEngine->cleanCompiled();
                    $this->templateEngine->cleanCache();

                    $static = AssetsServiceBuilder::build()->rebuildAll();
                    if (isset($static['css']['common'])) {
                        $sp->updateByName('mainfile_css', basename($static['css']['common']));
                    }
                    if (isset($static['js']['common'])) {
                        $sp->updateByName('mainfile_js', basename($static['js']['common']));
                    }
                    foreach ($static as $type => $packs) {
                        foreach ($packs as $pack => $file) {
                            $sp->updateByName('res_' . $type . '_' . $pack, basename($file));
                        }
                    }

                    if (!empty($req->commits[0])) {
                        $sp->updateByName('git_hash', $req->commits[0]->raw_node);
                    }

                    $this->logger->info('Результаты деплоя', ['output' => $res]);

                    $mail_attrs = [
                        'files_list' => implode('<br>', $res),
                    ];
                    Mailing::sendLetterCommon($config['git_report_email'], 2, $mail_attrs);
                }
                echo 'ok';
                exit();
            }
        }

        throw new NotFoundException();
    }

    /**
     * @param array $config
     *
     * @return DeployBitbucket
     */
    private function getBitbucketDeployHelper(array $config): DeployBitbucket
    {
        $guzzle = new Client();

        return new DeployBitbucket($guzzle, $this->logger, SENTRY_RELEASE_DSN, $config);
    }
}
