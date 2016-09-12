<?php
/**
 * View renderer for slim 3
 * supports theme by switching path
 * see examples/ for example themes
 *
 * This stays as a single instance so the attributes can be shared
 *
 * @link https://github.com/coreorm/slim3-view
 */
namespace Coreorm\Slim3;

use Psr\Http\Message\ResponseInterface;

/**
 * Class Theme
 * @package Coreorm\Slim3
 */
class Theme
{
    /**
     * single instance
     * @var Theme
     */
    private static $instance = null;

    /**
     * config
     * @var array
     */
    private $cnf = [
        'basePath' => '',
        'layout' => '',
        'theme' => '',
        'defaultLayout' => 'default',
        'defaultTheme' => 'default',
        'themePath' => null,
        'defaultThemePath' => null
    ];

    /**
     * data for view
     * @var array
     */
    protected $data = [];

    /**
     * single instance of view
     * @param null $templatePath
     * @param string $theme
     * @return Theme
     */
    public static function instance($templatePath = null, $theme = 'default')
    {
        if (empty(self::$instance)) {
            self::$instance = new self($templatePath, $theme);
        }
        return self::$instance;
    }

    /**
     * constructor
     * @param $templatePath
     * @param string $theme \
     */
    private function __construct($templatePath, $theme = 'default')
    {
        $this->cnf['basePath'] = realpath($templatePath);
        $this->setTheme($theme);
    }

    /**
     * set shared data (across all views)
     * @param $k
     * @param $v
     * @return $this
     */
    public function setData($k, $v)
    {
        $this->data[$k] = $v;
        return $this;
    }

    /**
     * set current theme
     * @param $theme
     */
    public function setTheme($theme)
    {
        $this->cnf['theme'] = $theme;
        $this->cnf['themePath'] = $this->cnf['basePath'] . DIRECTORY_SEPARATOR . $theme;
        $this->cnf['defaultThemePath'] = $this->cnf['basePath'] . DIRECTORY_SEPARATOR . $this->cnf['defaultTheme'];
    }

    /**
     * set current layout (run time)
     * default to 'default'
     * @param $layout
     */
    public function setLayout($layout)
    {
        $this->cnf['layout'] = $layout;
    }

    /**
     * include templates with a private scope
     * using a closure to ensure no $this in the scope
     * @param $template
     * @param array $data
     * @return string
     */
    public function scopedInclude($template, $data = [])
    {
        if (strpos($template, '.phtml') === false) {
            $template .= '.phtml';
        }

        $data = array_merge($this->data, $data);
        if (!is_file($template)) {
            throw new \RuntimeException('Template ' . $template . ' is not found.');
        }
        $view = new View($template, $data);
        return $view->render();
    }

    /**
     * NOTE: layout will have fall backs
     * @return string
     */
    protected function getLayoutPath()
    {
        $path = $this->cnf['themePath'] . DIRECTORY_SEPARATOR .
            'layouts' . DIRECTORY_SEPARATOR . $this->cnf['layout'];
        if (is_file($path)) {
            return $path;
        }
        // 1st level fallback: go back to default layout within the same theme
        $path = $this->cnf['themePath'] . DIRECTORY_SEPARATOR .
            'layouts' . DIRECTORY_SEPARATOR . $this->cnf['layout'];
        if (is_file($path)) {
            return $path;
        }
        // 2nd level fallback: use default layout file from default theme
        $path = $this->cnf['defaultThemePath'] . DIRECTORY_SEPARATOR .
            'layouts' . DIRECTORY_SEPARATOR . $this->cnf['defaultLayout'];
        // just return now - render will verify it again
        return $path;
    }


    /**
     * render the layout
     * @param $data string data for placeholder: mainContent
     * @return string
     */
    protected function renderLayout($data)
    {
        if (!is_string($data)) {
            throw new \InvalidArgumentException('Layout data must be string type');
        }
        return $this->scopedInclude($this->getLayoutPath(), [
            'mainContent' => $data
        ]);
    }

    /**
     * render a single view
     * @param $template
     * @param $data
     * @param bool|false $print
     * @return string
     */
    public function renderView($template, $data = [], $print = false)
    {
        $template = $this->cnf['themePath'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $template;
        $resp = $this->scopedInclude($template, $data);
        if ($print) {
            echo $resp;
        }
        return $resp;
    }

    /**
     * @param ResponseInterface $response
     * @param $template
     * @param array $data
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, $template, array $data = [])
    {
        $cnt = $this->renderView($template, $data);
        $output = $this->renderLayout($cnt);
        $response->getBody()->write($output);
        return $response;
    }
}
