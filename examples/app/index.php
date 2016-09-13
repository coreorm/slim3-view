<?php
require __DIR__ . '/../../vendor/autoload.php';

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
