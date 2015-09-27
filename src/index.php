<?php

try {
    // include the bootstrap
    include_once __DIR__ . '/../application/src/bootstrap.php';

    ob_start();

    // service the web
    $system = new ride\application\system\System($parameters);
    $system->setTimeZone();
    $system->service('web');
} catch (Exception $exception) {
    // error occured
    if (!headers_sent()) {
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
        header($protocol . ' 500 Internal Server Error');
    }

    while (@ob_end_flush());

    $view = new ride\web\mvc\view\ExceptionView($exception);
    $view->render(false);
}
