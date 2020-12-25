<?php

declare(strict_types=1);

namespace tests\sys;

use app\sys\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Smarty;

class TemplateEngineTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        define('_DIR_TEMPLATES', 'dir_tpl');
        define('_DIR_VAR', 'dir_var');
    }

    public function testContentBuilder(): void
    {
        $smarty = $this->getMockBuilder(Smarty::class)
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
            ])
            ->getMock();

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

        self:self::assertEquals('result', $result);
    }
}
