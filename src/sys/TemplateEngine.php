<?php

declare(strict_types=1);

namespace app\sys;

use Smarty;

class TemplateEngine
{
    /**
     * @var Smarty
     */
    private $smarty;

    /**
     * @param Smarty|null $smarty
     */
    public function __construct(Smarty $smarty = null)
    {
        $this->smarty = $smarty ?? new Smarty();
        $this->smarty->setTemplateDir(GLOBAL_DIR_TEMPLATES . '/');
        $this->smarty->setCompileDir(GLOBAL_DIR_VAR . '/templates_c/');
        $this->smarty->setCacheDir(GLOBAL_DIR_VAR . '/templates_cache/');
        $this->smarty->setCaching(Smarty::CACHING_OFF);
        $this->smarty->setCacheLifetime(3600);
        $this->smarty->setCompileCheck(Smarty::COMPILECHECK_ON);
        $this->smarty->setDebugging(false);
    }

    /**
     * @param null $template
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     * @return string
     */
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null): string
    {
        return $this->smarty->fetch($template, $cache_id, $compile_id, $parent);
    }

    /**
     * @param array|string $tpl_var
     * @param null $value
     * @param false $nocache
     * @return TemplateEngine|Smarty
     */
    public function assign($tpl_var, $value = null, $nocache = false)
    {
        return $this->smarty->assign($tpl_var, $value, $nocache);
    }

    /**
     * @param null $template
     * @param null $cache_id
     * @param null $compile_id
     * @param null $parent
     */
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null): void
    {
        $this->smarty->display($template, $cache_id, $compile_id, $parent);
    }

    /**
     * Очистка директории компилированных шаблонов
     */
    public function cleanCompiled(): void
    {
        foreach (glob($this->smarty->getCompileDir() . "*.php") as $filename) {
            unlink(realpath($filename));
        }
    }

    /**
     * Очистка директории кэша
     */
    public function cleanCache(): void
    {
        foreach (glob($this->smarty->getCacheDir() . "*.php") as $filename) {
            unlink(realpath($filename));
        }
    }

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function getContent(string $template, array $data = []): string
    {
        $this->assignArray($data);

        return $this->fetch($template);
    }

    /**
     * @param string $template
     * @param array $data
     */
    public function displayPage(string $template, array $data = []): void
    {
        $this->assignArray($data);
        $this->display($template);
    }

    /**
     * @param array $data
     */
    private function assignArray(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->assign($key, $value);
        }
    }
}
