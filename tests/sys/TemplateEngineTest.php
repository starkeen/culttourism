<?php

declare(strict_types=1);

namespace tests\sys;

use app\sys\TemplateEngine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smarty\Smarty;

class TemplateEngineTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        define('GLOBAL_DIR_TEMPLATES', 'dir_tpl');
        define('GLOBAL_DIR_VAR', 'dir_var');
    }

    public function testContentBuilder(): void
    {
        $smarty = $this->getSmartyMock();

        $smarty->expects(self::once())->method('setTemplateDir')->with('dir_tpl/');
        $smarty->expects(self::once())->method('setCompileDir')->with('dir_var/templates_c/');
        $smarty->expects(self::once())->method('setCacheDir')->with('dir_var/templates_cache/');
        $smarty->expects(self::once())->method('setCaching')->with(Smarty::CACHING_OFF);
        $smarty->expects(self::once())->method('setCacheLifetime')->with(3600);
        $smarty->expects(self::once())->method('setCompileCheck')->with(Smarty::COMPILECHECK_ON);
        $smarty->expects(self::once())->method('setDebugging')->with(false);

        $engine = new TemplateEngine($smarty);

        $smarty->expects(self::exactly(3))->method('assign');
        $smarty->expects(self::once())->method('fetch')->with('template.tpl')->willReturn('result');

        $result = $engine->getContent(
            'template.tpl',
            [
                'var1' => 'val1',
                'var2' => null,
                'var3' => 12345,
            ]
        );

        self::assertEquals('result', $result);
    }

    public function testContentDisplayPage(): void
    {
        $smarty = $this->getSmartyMock();
        $engine = new TemplateEngine($smarty);

        $smarty->expects(self::exactly(3))->method('assign');
        $smarty->expects(self::once())->method('display')->with('template.tpl');

        $engine->displayPage(
            'template.tpl',
            [
                'var1' => 'val1',
                'var2' => null,
                'var3' => 12345,
            ]
        );
    }

    public function testCleanCache(): void
    {
        $smarty = $this->getSmartyMock();
        $engine = new TemplateEngine($smarty);

        $smarty->expects(self::once())->method('getCacheDir')->willReturn('cache_dir');

        $engine->cleanCache();
    }

    public function testCleanCompiled(): void
    {
        $smarty = $this->getSmartyMock();
        $engine = new TemplateEngine($smarty);

        $smarty->expects(self::once())->method('getCompileDir')->willReturn('compile_dir');

        $engine->cleanCompiled();
    }

    /**
     * @return MockObject|Smarty
     */
    private function getSmartyMock(): MockObject
    {
        return $this->getMockBuilder(Smarty::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'assign',
                'fetch',
                'display',
                'setTemplateDir',
                'setCompileDir',
                'setCacheDir',
                'setCaching',
                'setCacheLifetime',
                'setCompileCheck',
                'setDebugging',
                'getCompileDir',
                'getCacheDir',
            ])
            ->getMock();
    }
}
