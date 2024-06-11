<?php

declare(strict_types=1);

namespace app\sys;

use Smarty\Smarty;

class TemplateEngine
{
    /**
     * @var Smarty
     */
    private Smarty $smarty;

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
     * @param  string $template
     * @return string
     */
    public function fetch(string $template): string
    {
        return $this->smarty->fetch($template, null, null, null);
    }

    /**
     * @param string $tplVar
     * @param string|int|mixed|null $value
     * @return Smarty
     */
    public function assign(string $tplVar, $value = null)
    {
        return $this->smarty->assign($tplVar, $value, false);
    }

    /**
     * @param string $template
     */
    public function display(string $template): void
    {
        $this->smarty->display($template, null, null, null);
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
     * @param  string $template
     * @param  array  $data
     * @return string
     */
    public function getContent(string $template, array $data = []): string
    {
        $this->assignArray($data);

        return $this->fetch($template);
    }

    /**
     * @param string $template
     * @param array  $data
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
