<?php

use app\core\SiteRequest;
use app\db\MyDB;
use app\exceptions\RedirectException;
use app\sys\DeployBitbucket;
use GuzzleHttp\Client;

/**
 * Модуль служебных и системных процессов
 */
class Page extends Core
{
    private const SETTINGS_BRANCH_DEPLOY = 9;

    /**
     * @inheritDoc
     */
    public function compileContent(): void
    {
        if ($this->siteRequest->getLevel2() !== null) {
            $this->processError(Core::HTTP_CODE_404);
        }

        if ($this->siteRequest->getLevel1() === null && empty($this->siteRequest->getGET())) {
            throw new RedirectException('/');
        } elseif ($this->siteRequest->getLevel1() === 'bitbucket' && $this->siteRequest->getGETParam('key') !== null) {
            $this->getBitbucket(trim($_GET['key']));
        } elseif ($this->siteRequest->getLevel1() === 'static' && $this->siteRequest->getGETParam('type') !== null && $this->siteRequest->getGETParam('pack') !== null) {
            $this->getStatic(trim($this->siteRequest->getGETParam('type')), trim($this->siteRequest->getGETParam('pack')));
        } else {
            $this->processError(Core::HTTP_CODE_404);
        }
    }

    /**
     * @param string $type
     * @param string $pack
     */
    public function getStatic(string $type, string $pack = 'common'): void
    {
        if ($type === 'css') {
            header('Content-Type: text/css');
        } elseif ($type === 'js') {
            header('Content-Type: text/javascript');
        } else {
            header('Content-Type: text/plain');
        }

        $sr = new StaticResources();
        echo $sr->getFull($type, $pack);
        exit();
    }

    /**
     * @param string|null $key
     */
    private function getBitbucket(string $key = null): void
    {
        if (isset($_POST) && !empty($_POST)) {
            $this->logger->info('Запрос на деплой', $_POST);
            $req = json_decode($_POST['payload']);

            $sp = new MSysProperties($this->db);
            $config = $sp->getSettingsByBranchId(self::SETTINGS_BRANCH_DEPLOY);

            if ($key && $key === $config['git_key']) {
                $config['location'] = _DIR_ROOT . '/';

                $bb = $this->getBitbucketDeployHelper($config);
                $res = $bb->deploy($req);

                if (!empty($res)) {
                    $this->smarty->cleanCompiled();
                    $this->smarty->cleanCache();

                    $sr = new StaticResources();
                    $static = $sr->rebuildAll();
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
            } else {
                $this->processError(Core::HTTP_CODE_404);
            }
        } else {
            $this->processError(Core::HTTP_CODE_404);
        }
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

    /**
     * @param MyDB $db
     * @param SiteRequest $request
     *
     * @return self
     */
    public static function getInstance(MyDB $db, SiteRequest $request): self
    {
        return self::getInstanceOf(__CLASS__, $db, $request);
    }
}
