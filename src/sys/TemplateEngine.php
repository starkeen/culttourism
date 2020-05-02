<?php

declare(strict_types=1);

namespace app\sys;

use Smarty;

class TemplateEngine extends Smarty
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplateDir(_DIR_TEMPLATES . '/');
        $this->setCompileDir(_DIR_ROOT . '/templates_c/');
        $this->setCacheDir(_DIR_ROOT . '/templates_cache/');

        $this->caching = Smarty::CACHING_OFF;
        $this->cache_lifetime = 3600;
        $this->compile_check = true;

        $this->debugging = false;
    }

    public function cleanCompiled(): void
    {
        foreach (glob($this->compile_dir . "*.php") as $filename) {
            unlink(realpath($filename));
        }
    }

    public function cleanCache(): void
    {
        foreach (glob($this->cache_dir . "*.php") as $filename) {
            unlink(realpath($filename));
        }
    }
}
