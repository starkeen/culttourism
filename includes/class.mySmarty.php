<?php

include_once(_DIR_ADDONS . '/smarty/libs/Smarty.class.php');

class mySmarty extends Smarty {

    public function __construct($module = null) {
        parent::__construct();
        $this->template_dir = _DIR_TEMPLATES . '/';
        $this->compile_dir = _DIR_ROOT . '/templates_c/';
        //$this->config_dir = _DIR_ROOT . 'configs/';
        $this->cache_dir = _DIR_ROOT . '/templates_cache/';

        $this->caching = FALSE;
        $this->cache_lifetime = 3600;
        $this->compile_check = true;

        $this->debugging = FALSE;
    }

    public function cleanCompiled() {
        foreach (glob($this->compile_dir . "*.php") as $filename) {
            unlink(realpath($filename));
        }
    }

    public function cleanCache() {
        foreach (glob($this->cache_dir . "*.php") as $filename) {
            unlink(realpath($filename));
        }
    }

}
