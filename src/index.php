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

    ob_start();

    // service the web
    $system = new ride\application\system\System($parameters);
    $system->setTimeZone();
    $system->service('web');
} catch (Exception $exception) {
    // error occured
    $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
    header($protocol . ' 500 Internal Server Error');

    while (@ob_end_flush());

    $view = new ride\web\mvc\view\ExceptionView($exception);
    $view->render(false);
}
