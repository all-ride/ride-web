<?php

namespace ride\web\mvc\controller;

use ride\library\config\Config;
use ride\library\dependency\DependencyInjector;
use ride\library\event\Event;
use ride\library\http\Header;
use ride\library\mvc\controller\AbstractController as LibAbstractController;
use ride\library\mvc\exception\MvcException;
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
     * @deprecated
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
     * @deprecated
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
    * Gets the web application
    * @return \ride\library\system\System
    */
    protected function getWeb() {
        return $this->dependencyInjector->get('ride\\web\\WebApplication');
    }

    /**
     * Gets the config
     * @return \ride\library\config\Config
     */
    protected function getConfig() {
        return $this->dependencyInjector->get('ride\\library\\config\\Config');
    }

    /**
     * Gets the system
     * @return \ride\library\system\System
     */
    protected function getSystem() {
        return $this->dependencyInjector->get('ride\\library\\system\\System');
    }

    /**
     * Gets the log
     * @return \ride\library\log\Log
     */
    protected function getLog() {
        return $this->dependencyInjector->get('ride\\library\\log\\Log');
    }

    /**
     * Gets the referer of the current request
     * @param string $default Default referer to return when there is no
     * referer set
     * @return string URL to the last page displayed
     */
    protected function getReferer($default = null) {
        $referer = $this->request->getQueryParameter('referer');
        if (!$referer) {
            $referer = $this->request->getHeader(Header::HEADER_REFERER);
        }

        if (!$referer) {
            return $default;
        }

        return $referer;
    }

    /**
     * Gets the URL of the provided route
     * @param string $routeId The id of the route
     * @param array $arguments Path arguments for the route
     * @param array $queryParameters Array with the query parameter name as key
     * and the parameter as value.
     * @param string $querySeparator Separator between the query parameters
     * @return string
     * @throws \ride\library\router\exception\RouterException If the route is
     * not found
     */
    protected function getUrl($routeId, array $arguments = null, array $queryParameters = null, $querySeparator = '&') {
        return $this->getWeb()->getUrl($routeId, $arguments, $queryParameters, $querySeparator);
    }

    /**
     * Sets the provided data as a json view
     * @param mixed $data
     * @param integer $options Options for the json_encode function
     * @return null
     */
    protected function setJsonView($data, $options = JSON_PRETTY_PRINT) {
        $this->response->setHeader(Header::HEADER_CONTENT_TYPE, 'application/json');
        $this->response->setView(new JsonView($data, $options));
    }

    /**
     * Sets a file view for the provided file to the response
     * @param \ride\library\system\file\File $file File which needs to be
     * offered for download
     * @param string $name Name for the download
     * @param boolean $cleanUp Set to true to add an event to delete the file
     * after the response has been sent
     * @param string $mime MIME type for the response headers, when not
     * provided, it will be sniffed
     * @return null
     */
    protected function setFileView(File $file, $name = null, $cleanUp = false, $mime = null) {
        if ($name === null) {
            $name = $file->getName();
        }

        $userAgent = $this->request->getHeader(Header::HEADER_USER_AGENT);
        if ($userAgent && strstr($userAgent, "MSIE")) {
            $name = preg_replace('/\./', '%2e', $name, substr_count($name, '.') - 1);
        }

        if ($mime === null) {
            $mimeService = $this->dependencyInjector->get('ride\\service\\MimeService');
            $mediaType = $mimeService->getMediaTypeForFile($file);
            if (!$mediaType) {
                throw new MvcException('Could not detect MIME for the provided file');
            }

            $mime = (string) $mediaType;
        }

        $view = new FileView($file);

        $this->response->setHeader(Header::HEADER_CACHE_CONTROL, 'no-cache, must-revalidate');
        $this->response->setHeader(Header::HEADER_CONTENT_DISPOSITION, 'inline; filename="' . $name . '"');
        $this->response->setHeader(Header::HEADER_CONTENT_TYPE, $mime);
        $this->response->setView($view);

        if ($cleanUp) {
            $this->cleanUpDownload($file);
        }
    }

    /**
     * Sets a download view for the provided file to the response
     * @param \ride\library\system\file\File $file File which needs to be
     * offered for download
     * @param string $name Name for the download
     * @param boolean $cleanUp Set to true to add an event to delete the file
     * after the response has been sent
     * @param string $mime MIME type for the response headers, when not
     * provided, it will be sniffed
     * @return null
     */
    protected function setDownloadView(File $file, $name = null, $cleanUp = false, $mime = null) {
        $this->setFileView($file, $name, $cleanUp, $mime);

        $this->response->setHeader(Header::HEADER_CONTENT_DESCRIPTION, 'File Transfer');
        $this->response->setHeader(Header::HEADER_CONTENT_DISPOSITION, 'attachment; filename="' . $name . '"');
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
