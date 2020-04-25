<?php

use app\db\MyDB;
use app\sys\DeployBitbucket;

/**
 * Модуль служебных и системных процессов
 */
class Page extends PageCommon
{
    private const SETTINGS_BRANCH_DEPLOY = 9;
    private const MODULE_KEY = 'sys';

    public function __construct($db, $mod)
    {
        [$module_id, $page_id, $id] = $mod;

        parent::__construct($db, self::MODULE_KEY, $page_id);

        if ($page_id == '' && $id == '' && empty($_GET)) {
            $this->getError(Core::HTTP_CODE_301, '');
        } elseif ($page_id === 'bitbucket' && $id == '' && isset($_GET['key'])) {
            $this->getBitbucket(trim($_GET['key']));
        } elseif ($page_id === 'static' && $id == '' && isset($_GET['type']) && isset($_GET['pack'])) {
            $this->getStatic(trim($_GET['type']), trim($_GET['pack']));
        } else {
            $this->getError(Core::HTTP_CODE_404);
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
            Logging::addHistory('sys', 'Запрос на деплой', $_POST);
            $req = json_decode($_POST['payload']);

            $sp = new MSysProperties($this->db);
            $config = $sp->getSettingsByBranchId(self::SETTINGS_BRANCH_DEPLOY);

            if ($key && $key === $config['git_key']) {
                $config['location'] = _DIR_ROOT . '/';

                $bb = new DeployBitbucket($config);
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

                    Logging::addHistory('sys', 'Результаты деплоя', $res);

                    $mail_attrs = [
                        'files_list' => implode('<br>', $res),
                    ];
                    Mailing::sendLetterCommon($config['git_report_email'], 2, $mail_attrs);
                }
                echo 'ok';
                exit();
            } else {
                $this->content = $this->getError('404');
            }
        } else {
            $this->content = $this->getError('404');
        }
    }

    /**
     * @param MyDB $db
     * @param string $mod
     *
     * @return Core
     */
    public static function getInstance(MyDB $db, $mod): self
    {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }
}
