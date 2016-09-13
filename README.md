# slim3-view

View renderer for slim3 with very straightforward and simple (yet still customisable) theme/layout/templates structure.

This also works with any PSR-7 compliant response objects.

## Requirement

- php 5.6+
- composer

## Install

`composer require coreorm/slim3-view`
 
## Test
 `phpunit`
 
## Start Example Server
 `./start.sh` and browse to [http://localhost:8000/](http://localhost:8000/)
 
## Usage
 
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

#### Example (or just open up examples/app/index.php):

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


### APIs

#### - instantiate the theme class

We use a singleton pattern to ensure all shared data are available to each and every template, so simply use:

```
use \Coreorm\Slim3\Theme;

$theme = Theme::instance('theme base directory', 'layout name');
```

#### - switch layout

It's possible to switch layout either from the beginning of the code, or inside the routes at run time.

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

If layout file is not found, it will default back to `default.phtml` if exists in the current theme directory, otherwise it will default back to default layout inside default theme.

#### - render a template plus the layout

```
$theme->render($response, 'relative path/template name', [partial data]);
```

This will render the template then in turn render the chosen layout and output to the browser.

#### - render a template and retrieve the content

```
$viewSrcHTML = $theme->renderView('relative path/template name', [partial data]);
```

#### - import a sub template inside a template

In the template code, do:
```
$this->import('relative path/template name', [partial data]);
```

### Data scope

- `$theme->setData($k, $v)` sets data that is available for all templates/layouts;
- passing partial data to the render/import function will set data that is private to the given template only. e.g. `$theme->render($response, 'page', ['foo' => 'bar'])` will set the $foo value to 'bar' only for the `page` template.
