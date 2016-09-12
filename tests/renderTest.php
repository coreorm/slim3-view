<?php
use PHPUnit\Framework\TestCase;
use Coreorm\Slim3\Theme;
use Slim\Http\Response;

class RenderTest extends TestCase
{
    public function testRender()
    {
        $theme = Theme::instance(__DIR__ . '/../examples/themes', 'tests');
        $theme->setData('foo', 'old-bar');
        $resp = $theme->render(new Response(), 'bar', [
            'foo' => 'new-bar'
        ]);
        $html = (string)$resp->getBody();

        // verify output string has valid data
        $this->assertContains('<title>', $html);
        $this->assertNotContains('new bar', $html);
    }
}
