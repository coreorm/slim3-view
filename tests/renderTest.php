<?php
use PHPUnit\Framework\TestCase;
use Coreorm\Slim3\Theme;
use Slim\Http\Response;

class RenderTest extends TestCase
{
    public function testRender()
    {
        $theme = Theme::instance(__DIR__ . '/themes', 'tests');
        $theme->setData('foo', 'old-bar');
        $theme->setTheme('tests');
        $resp = $theme->render(new Response(), 'bar', [
            'foo' => 'new-bar'
        ]);
        $html = (string)$resp->getBody();

        // verify output string has valid data
        $this->assertContains('<title>', $html);
        $this->assertNotContains('new bar', $html);
    }

    public function testFallbackRender()
    {
        $theme = Theme::instance(__DIR__ . '/../examples/themes', 'tests');
        $theme->share('views/foo', 'tests');
        // try render an non-existent theme with a fallback and it should not break
        $this->assertEquals($src = $theme->setTheme('non-existent')->renderView('foo'), 'true');
        $theme->setTheme('tests');
    }

    public function testReusableRender()
    {
        $theme = Theme::instance(__DIR__ . '/../examples/themes', 'tests');
        $cnt = 500;
        $benchmarker = function ($cache) use ($theme, $cnt) {
            $t = microtime(true);
            for ($i = 0; $i <= $cnt; $i++) {
                $theme->renderView('sub/repeater', [], false, $cache);
            }
            return number_format(microtime(true) - $t, 6) * 1000;
        };
        $cachedTime = $benchmarker(true);
        $uncachedTime = $benchmarker(false);
        $this->assertTrue($cachedTime < $uncachedTime);
        echo "\nRender {$cnt} times: \ncached: {$cachedTime}ms VS. uncached: {$uncachedTime} ms";
    }
}
