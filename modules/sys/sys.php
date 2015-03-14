<?php

/**
 * Description of header of "Sys" module
 *
 * @author starkeen
 */
class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        parent::__construct($db, 'sys', $page_id);

        if ($page_id == '' && $id == '' && empty($_GET)) {
            $this->content = $this->getError('301', '');
        } elseif ($page_id == 'bitbucket' && $id == '' && isset($_GET['key'])) {
            $this->content = $this->getBitbucket(trim($_GET['key']));
        } elseif ($page_id == 'static' && $id == '' && isset($_GET['type']) && isset($_GET['pack'])) {
            $this->content = $this->getStatic(trim($_GET['type']), trim($_GET['pack']));
        } else {
            $this->content = $this->getError('404');
        }
    }

    public function getStatic($type, $pack = 'common') {
        if ($type == 'css') {
            header("Content-Type: text/css");
        } elseif ($type == 'js') {
            header("Content-Type: text/javascript");
        } else {
            header("Content-Type: text/plain");
        }
        $sr = new StaticResources();
        echo $sr->getFull($type, $pack);
        exit();
    }

    private function getBitbucket($key = null) {
        if (isset($_POST) && !empty($_POST)) {
            Logging::addHistory('sys', "Запрос на деплой", $_POST);
            $req = json_decode($_POST['payload']);

            $sp = new MSysProperties($this->db);
            $config = $sp->getSettingsByBranchId(9);

            if ($key && $key == $config['git_key']) {
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

                    Logging::addHistory('sys', "Результаты деплоя", implode("\n", $res));

                    $mail_attrs = array(
                        'files_list' => implode("<br>", $res),
                    );
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

    private function getRepoConfig() {
        $sp = new MSysProperties($this->db);
        return $sp->getSettingsByBranchId(9);
    }

    public static function getInstance($db, $mod) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
