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
        'customPaths' => [],
        'sharedPaths' => []
    ];

    /**
     * data for view
     * @var array
     */
    protected $data = [];

    /**
     * reusable HTML cache
     * @var array
     */
    protected $reusableHTML = [];

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
     * get absolute path for given relative path (against theme)
     * @param $relativePath
     * @return string
     */
    private function getAbsolutePath($relativePath)
    {
        $path = $this->cnf['basePath'] . DIRECTORY_SEPARATOR . $relativePath;
        if (strpos($path, '.phtml') === false) {
            $path .= '.phtml';
        }
        return $path;
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
     * get data
     * @param $k
     * @param null $default
     * @return null
     */
    public function getData($k, $default = null)
    {
        if (!empty($this->data[$k])) {
            return $this->data[$k];
        }
        return $default;
    }

    /**
     * set current theme
     * @param $theme
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->cnf['theme'] = $theme;
        $this->cnf['themePath'] = $this->cnf['basePath'] . DIRECTORY_SEPARATOR . $theme;
        // define the shared theme
        $this->share('layouts/default', 'default/layouts/default');
        return $this;
    }

    /**
     * mark a template from a theme as shareable - so when app is trying to render a file with the same name
     * but from a different theme, it will fall back to this one when the file does not exist in the different theme.
     * @param $templateRelativePath string - make sure you include the views/layouts dir names in the path
     * @param $theme
     */
    public function share($templateRelativePath, $theme)
    {
        // theme must exist
        $path = $this->getAbsolutePath($theme . DIRECTORY_SEPARATOR . $templateRelativePath);
        if (!is_file($path)) {
            throw new \RuntimeException("Unable to share template {$templateRelativePath} as it doesn't exist.");
        }
        $this->cnf['sharedPaths'][$templateRelativePath] = $path;
    }

    /**
     * set current layout (run time)
     * default to 'default'
     * @param $layout
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->cnf['layout'] = $layout;
        return $this;
    }

    /**
     * include templates with a private scope
     * using a closure to ensure no $this in the scope
     * @param $template
     * @param array $data
     * @param bool $reuseHTML if true, this template will render ever once and be reused afterwards
     * @return string
     */
    protected function scopedInclude($template, $data = [], $reuseHTML = false)
    {
        // if reuseHTML is true, no need to render
        if ($reuseHTML && !empty($this->reusableHTML[$template])) {
            return $this->reusableHTML[$template];
        }

        $data = array_merge($this->data, $data);
        if (!is_file($template)) {
            throw new \RuntimeException('Template ' . $template . ' is not found.');
        }

        $view = new View($template, $data);

        $src = $view->render();
        if ($reuseHTML) {
            $this->reusableHTML[$template] = $src;
        }

        return $src;
    }

    /**
     * NOTE: layout will have fall backs
     * @return string
     */
    protected function getLayoutPath()
    {
        $path = $this->getAbsolutePath("{$this->cnf['theme']}/layouts/{$this->cnf['layout']}");
        if (is_file($path)) {
            return $path;
        }

        // 1st level: go back to shared
        if (!empty($this->cnf['sharedPaths']["layouts/{$this->cnf['layout']}"])) {
            return $this->cnf['sharedPaths']["layouts/{$this->cnf['layout']}"];
        }

        // 2nd level: go back to default shared (by default, /default/layouts/default.phtml is shared)
        if (!empty($this->cnf['sharedPaths']['layouts/default'])) {
            return $this->cnf['sharedPaths']['layouts/default'];
        }
        // if it gets here, it's broken
        throw new \RuntimeException('Unable to retrieve the current layout template');
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
     * @param bool $shouldFallback this will automatically fall back to shared template if not found
     * @param bool $reuseHTML if true, this template will render ever once and be reused afterwards
     * @return string
     */
    public function renderView($template, $data = [], $shouldFallback = true, $reuseHTML = false)
    {
        $relativePath = 'views' . DIRECTORY_SEPARATOR . $template;
        $absPath = $this->getAbsolutePath($this->cnf['theme'] . DIRECTORY_SEPARATOR . $relativePath);

        if ($shouldFallback && !is_file($absPath)) {
            if (!empty($this->cnf['sharedPaths'][$relativePath])) {
                $absPath = $this->cnf['sharedPaths'][$relativePath];
            } else {
                // unable to render - invalid
                throw new \RuntimeException('unable to find fallback template for ' . $template);
            }
        }

        return $this->scopedInclude($absPath, $data, $reuseHTML);
    }

    /**
     * @param ResponseInterface $response
     * @param $template
     * @param array $data
     * @param bool $shouldFallback this will automatically fall back to shared template if not found
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, $template, array $data = [], $shouldFallback = false)
    {
        $cnt = $this->renderView($template, $data, $shouldFallback);
        $output = $this->renderLayout($cnt);
        $response->getBody()->write($output);
        return $response;
    }
}
