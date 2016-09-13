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
 `./start.sh`
 
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

Example (or just open up examples/app/index.php):

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

If you want to use it with other PSR-7 compliant library, just do
```
$response = new Response();
$response = Theme::instance('theme-base-dir', 'theme-name')->render($response, 'template-name', [data array]);
echo $response->getBody();
```

## Advanced usage

Within the template, you can use `$this->import(template-name, partial-data)` to render a sub template. 

E.g.
```
<p><?php $this->import('sub/foo', ['foo' => 'bar']) ?></p>
```

Switch layout at run time: simply call `$theme->setLayout(layout name)`, e.g.

```
$app->get('/', function ($request, $response, $args) use ($theme) {
    $theme->setLayout('new-layout');
    return $theme->render($response, 'index', [
        'foo' => 'bar'
    ]);
});
```

If layout file is not found, it will default back to 'default.phtml' if exists in the current theme directory, otherwise it will default back to default layout inside default theme.
