<?php

/**
 * Description of header of "Sys" module
 *
 * @author starkeen
 */
class Page extends Page_common {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        parent::__construct($db, 'sys', $page_id);

        if ($page_id == '' && $id == '' && empty($_GET)) {
            $this->content = $this->getError('301', '');
        } elseif ($page_id == 'bitbucket' && $id == '' && isset($_GET['key'])) {
            $this->content = $this->getBitbucket(trim($_GET['key']));
        } else {
            $this->content = $this->getError('404');
        }
    }

    private function getBitbucket($key = null) {
        if (isset($_POST) && !empty($_POST)) {
            Logging::addHistory('sys', "Запрос на деплой", $_POST);
            $req = json_decode($_POST['payload']);

            $config = $this->getRepoConfig();

            if ($key && $key == $config['git_key']) {
                $config['location'] = _DIR_ROOT . '/';

                $bb = new DeployBitbucket($config);
                $res = $bb->deploy($req);

                if (!empty($res)) {
                    $this->smarty->cleanCompiled();
                    $this->smarty->cleanCache();
                    
                    Logging::addHistory('sys', "Результаты деплоя", implode("\n", $res));
                    
                    $mail_attrs = array(
                        'files_list' => implode("<br>", $res),
                    );
                    Mailing::sendLetterCommon($config['git_report_email'], 15, $mail_attrs);
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

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}
