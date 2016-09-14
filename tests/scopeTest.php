<?php
use PHPUnit\Framework\TestCase;
use Coreorm\Slim3\Theme;

class ScopeTest extends TestCase
{
    public function testViewRenderWithPrivateScope()
    {
        $theme = Theme::instance(__DIR__ . '/themes', 'tests');
        // test if response contains shared data (private scope shared data should be overwritten)
        $theme->setData('foo', 'shared data');
        $res = $theme->renderView('bar', ['foo' => 'private data']);
        $this->assertContains('private data', $res, 'only private data is included');
        $this->assertNotContains('shared data', $res, 'only private data is included');
    }
}
