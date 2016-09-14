# slim3-view

View renderer for slim3 with very straightforward and simple (yet still customisable) theme/layout/templates structure.

This also works with any PSR-7 compliant response objects.

## Example 
- live demo: [https://slim3-view-example.herokuapp.com/](https://slim3-view-example.herokuapp.com/)
- source: [https://github.com/coreorm/slim3-view-example](https://github.com/coreorm/slim3-view-example)

## Requirement

- php 5.6+
- composer

## Install

`composer require coreorm/slim3-view`
 
## Test
 `phpunit`
 
## Structure
 
make sure your themes directory are in the following structure:

```
themes
├── default
│   └── layouts
│       └── default.phtml
├── theme 1
│   ├── layouts
│   │   └── main.phtml
│   └── views
│       └── index.phtml
...
```

- Layout: layout files are to be placed in `<theme name>/layouts/` directory.
- Note that a default layout is recommended in case a custom layout is not found, it will fall back to the default one.
- Views: views must be under views, but can be in nested directories, when you need to render it, simply use the relative path against views, e.g. if you have `views/foo/bar.phtml`, simply call `$theme->render($response, 'foo/bar')`

## Layout template

Layout template must contain a default variable `$mainContent` in the body so sub templates can appear, e.g.

```
# file: themes/default/layouts/default.phtml

...
<body><?php echo $mainContent; ?></body>
...
```

Another way to send rendered content to the page is to use `$theme->renderView()`, e.g.

```
# in route
$theme->setData('myHeader', $theme->renderView('shared/header'));

# in layout

...
<head>
echo $myHeader
</head>
...
```

## Usage Example (or just open up examples/app/index.php):

* Use with slim

```
use \Coreorm\Slim3\Theme;

$app = new Slim\App();

$theme = Theme::instance(__DIR__ . '/../themes', 'example');
// set layout
$theme->setLayout('main')->setData('title', 'Slim 3 View Example');

$app->get('/', function ($request, $response, $args) use ($theme) {
    return $theme->render($response, 'index', [
        'foo' => 'bar'
    ]);
});

$app->run();
```

* Use with other PSR-7 compliant library

```
$response = new Response();
$response = Theme::instance('theme-base-dir', 'theme-name')->render($response, 'template-name', [data array]);
echo $response->getBody();
```


## APIs
### Theme APIs
#### Theme::instance($templatePath = null, $theme = 'default') 
**instantiate the theme class**: We use a singleton pattern to ensure all shared data are available to each and every template, so simply use:

```
use \Coreorm\Slim3\Theme;

$theme = Theme::instance('theme base directory', 'layout name');
```

#### $theme->setLayout($layout) 
**switch layout**: It's possible to switch layout either from the beginning of the code, or inside the routes at run time.

```
$theme->setLayout('layout name');
```

E.g.

```
$app->get('/', function ($request, $response, $args) use ($theme) {
    $theme->setLayout('new-layout');
    return $theme->render($response, 'index', [
        'foo' => 'bar'
    ]);
});
```

#### $theme->setTheme($theme)
**switch theme**: This will set the current theme to the string value of `$theme`.

#### $theme->share($templateRelativePath, $theme)
**mark a template from a specific theme as shareable**: this will essentially make it a global template to go to when a template with the same name from a different theme is not found.

- `$templateRelativePath` the relative path to the template
- `$theme` the theme name

E.g.
```
$theme->share('views/page1', 'default');
```
This will mark the page `themes/default/views/page1.phtml` as the shared view for relative path `views/page1`, so if say I'm in theme 'new' and I call `renderView('page1')`, if `themes/new/views/page1.phtml` doesn't exist, it will use `themes/default/views/page1.phtml` instead.


#### $theme->render(ResponseInterface $response, $template, array $data = [], $shouldFallback = false)
**render a template plus the layout**: This is the final render function you'd want to run in your route/controller, as it renders the give template and assigns it to the `$mainContent` partial data which in turn will be displayed in the template main body (or wherever you would like to, by calling `<?php echo $mainContent; ?>`). So it renders template, then layout, and then output to the browser.

- `$shouldFallback` - when this is set to true, the system will try to find the fallback template if the given template is not found (see above `share` API for more details).

E.g.

```
$theme->render($response, 'relative path/template name', [partial data], true);
```

#### $theme->renderView($template, $data = [], $shouldFallback = true, $reuseHTML = false)
**render a template and retrieve the content**: This renders a template and will return the html.

`$reuseHTML` if this is set to true, the given template will render only once, and any future renderView/import calls to this template will reuse the HTML that's rendered from the very first one. Use this for elements that are repeated on the same page with exactly the same HTML source.

E.g.

```
$viewSrcHTML = $theme->renderView('relative path/template name', [partial data]);
```

### Template (layout/views) API
#### $this->import($templateFile, $data = [], $shouldFallback = true, $reuseHTML = false)
**import a sub template inside a template** This is only available inside the template itself.

In the template code, do:
```
$this->import('relative path/template name', [partial data]);
```

## Data scope

### Global (data available to all templates)

`$theme->setData($k, $v)` sets data that is available for all templates/layouts;

### Private (partial data that will override global data, when used for rendering individual views)
passing partial data to the render/import function will set data that is private to the given template only. 
e.g. `$theme->render($response, 'page', ['foo' => 'bar'])` will set the $foo value to 'bar' only for the `page` template.
