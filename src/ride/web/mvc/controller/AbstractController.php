<?php

namespace ride\web\mvc\controller;

use ride\library\config\Config;
use ride\library\dependency\DependencyInjector;
use ride\library\event\Event;
use ride\library\http\Header;
use ride\library\mvc\controller\AbstractController as LibAbstractController;
use ride\library\system\file\File;

use ride\web\mvc\view\FileView;
use ride\web\mvc\view\JsonView;
use ride\web\WebApplication;

/**
 * Abstract implementation of a controller with some helper methods
 */
abstract class AbstractController extends LibAbstractController {

    /**
     * Instance of the configuration
     * @var \ride\library\config\Config
     */
    protected $config;

    /**
     * Instance of the dependency injector
     * @var \ride\library\dependency\DependencyInjector
     */
    protected $dependencyInjector;

    /**
     * Sets the instance of the configuration
     * @param \ride\library\config\Config $config
     * @return null
     */
    public function setConfig(Config $config) {
        $this->config = $config;
    }

    /**
     * Sets the instance of the dependency injector
     * @param \ride\library\dependency\DependencyInjector $dependencyInjector
     * @return null
     */
    public function setDependencyInjector(DependencyInjector $dependencyInjector) {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Gets the URL of the provided route
     * @param string $routeId The id of the route
     * @param array $arguments Path arguments for the route
     * @return string
     * @throws \ride\library\router\exception\RouterException If the route is
     * not found
     */
    protected function getUrl($routeId, array $arguments = null) {
        $router = $this->dependencyInjector->get('ride\\library\\router\\Router');
        $routeContainer = $router->getRouteContainer();

        return $routeContainer->getUrl($this->request->getBaseScript(), $routeId, $arguments);
    }

    /**
     * Sets the provided data as a json view
     * @param mixed $data
     * @return null
     */
    protected function setJsonView($data) {
        $this->response->setHeader(Header::HEADER_CONTENT_TYPE, 'application/json');
        $this->response->setView(new JsonView($data));
    }

    /**
     * Sets a download view for the provided file to the response
     * @param \ride\library\system\file\File $file File which needs to be
     * offered for download
     * @param string $name Name for the download
     * @param boolean $cleanUp Set to true to add an event to delete the file
     * after the response has been sent
     * @return null
     */
    protected function setDownloadView(File $file, $name = null, $cleanUp = false) {
        if ($name === null) {
            $name = $file->getName();
            $mimeFile = $file;
        } else {
            $mimeFile = $file->getChild($name);
        }

        $userAgent = $this->request->getHeader(Header::HEADER_USER_AGENT);
        if ($userAgent && strstr($userAgent, "MSIE")) {
            $name = preg_replace('/\./', '%2e', $name, substr_count($name, '.') - 1);
        }

        $mimeResolver = $this->dependencyInjector->get('ride\\web\\mime\\MimeResolver');
        $mime = $mimeResolver->getMimeTypeByExtension($mimeFile->getExtension());

        $view = new FileView($file);

        $this->response->setHeader(Header::HEADER_CACHE_CONTROL, 'no-cache, must-revalidate');
        $this->response->setHeader(Header::HEADER_CONTENT_TYPE, $mime);
        $this->response->setHeader(Header::HEADER_CONTENT_DESCRIPTION, 'File Transfer');
        $this->response->setHeader(Header::HEADER_CONTENT_DISPOSITION, 'attachment; filename="' . $name . '"');
        $this->response->setView($view);

        if ($cleanUp) {
            $this->cleanUpDownload($file);
        }
    }

    /**
     * Cleans up the download file
     * @param \ride\library\system\file\File $file File to clean up after the response
     * @param \ride\library\event\Event $event Triggered event
     * @return null
     */
    public function cleanUpDownload(File $file = null, Event $event = null) {
        if ($file) {
            $this->downloadFile = $file;

            $eventManager = $this->dependencyInjector->get('ride\\library\\event\\EventManager');
            $eventManager->addEventListener(WebApplication::EVENT_POST_RESPONSE, array($this, 'cleanUpDownload'));
        } elseif (isset($this->downloadFile) && $this->downloadFile) {
            $this->downloadFile->delete();
        }
    }

}