<?php

class mySmarty extends Smarty {

    public function __construct($module = null) {
        parent::__construct();
        $this->setTemplateDir(_DIR_TEMPLATES . '/');
        $this->setCompileDir(_DIR_ROOT . '/templates_c/');
        $this->setCacheDir(_DIR_ROOT . '/templates_cache/');

        $this->caching = Smarty::CACHING_OFF;
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
