<?php
/**
 * View renderer for slim 3
 * supports theme by switching path
 * see examples/ for example themes
 *
 * @link https://github.com/coreorm/slim3-view
 */
namespace Coreorm\Slim3;

/**
 * Class View
 * the purpose of this class is to enable true private scope per view to avoid clashes.
 * @package Coreorm\Slim3
 */
class View
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $templateFile =  '';

    /**
     * constructor
     * @param $templateFile
     * @param $data
     */
    public function __construct($templateFile, $data)
    {
        $this->templateFile = $templateFile;
        $this->data = $data;
    }

    /**
     * import a template - this is for template files so they can use
     * $this->import($templateFile, $data) for sub templates
     * @param $templateFile
     * @param $data
     * @return mixed
     */
    protected final function import($templateFile, $data = [])
    {
        return Theme::instance()->renderView($templateFile, $data, true);
    }

    /**
     * actual rendering
     * @return string
     */
    public function render()
    {
        ob_start();
        extract($this->data);
        include $this->templateFile;
        return ob_get_clean();
    }

}
