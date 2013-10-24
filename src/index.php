<?php

$bootstrap = __DIR__ . '/../vendor/autoload.php';
$parameters = __DIR__ . '/../application/config/parameters.php';

try {
    // include the bootstrap
    include_once $bootstrap;

    // read the parameters
    if (file_exists($parameters)) {
        include_once $parameters;
    }

    if (!is_array($parameters)) {
        $parameters = null;
    }

    // service the cli
    $system = new pallo\application\system\System($parameters);
    $system->service('web');
} catch (Exception $exception) {
    // error occured
    $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
    header($protocol . ' 500 Internal Server Error');

    $view = new pallo\web\mvc\view\ExceptionView($exception);
    $view->render(false);
}