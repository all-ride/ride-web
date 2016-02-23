<?php

namespace ride\web\http;

use ride\library\mime\sniffer\MimeSniffer;
use ride\library\http\exception\HttpException;
use ride\library\http\HttpFactory as LibHttpFactory;
use ride\library\system\file\browser\FileBrowser;
use ride\library\system\file\File;

use ride\web\mvc\Request;

/**
 * Factory for HTTP objects
 */
class HttpFactory extends LibHttpFactory {

    /**
     * Instance of the file browser
     * @var \ride\library\system\file\browser\FileBrowser
     */
    protected $fileBrowser;

    /**
    * Instance of the MIME sniffer
    * @var \ride\library\mime\sniffer\MimeSniffer
    */
    protected $mimeSniffer;

    /**
     * Name of the session cookie variable
     * @var string
     */
    protected $sessionCookieName;

    /**
     * Constructs the file browser
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser
     * @param \ride\library\mime\sniffer\MimeSniffer $mimeSniffer
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, MimeSniffer $mimeSniffer) {
        $this->fileBrowser = $fileBrowser;
        $this->mimeSniffer = $mimeSniffer;
    }

    /**
     * Sets the name of the session cookie variable
     * @param string $sessionCookieName
     * @return null
     */
    public function setSessionCookieName($sessionCookieName = null) {
        $this->sessionCookieName = $sessionCookieName;
    }

    /**
     * Creates a request from a raw request string
     * @param string $data Raw HTTP request
     * @return Request
     */
    public function createRequestFromString($data) {
        $request = parent::createRequestFromString($data);

        $this->setRequestSessionCookieName($request);

        return $request;
    }

    /**
     * Creates a request from the $_SERVER variable
     * @return Request
     */
    public function createRequestFromServer() {
        $request = parent::createRequestFromServer();

        $this->setRequestSessionCookieName($request);

        return $request;
    }

    /**
     * Sets the session cookie name to the request
     * @param mixed $request
     * @return null
     */
    private function setRequestSessionCookieName($request) {
        if ($this->sessionCookieName && $request instanceof Request) {
            $request->setSessionCookieName($this->sessionCookieName);
        }
    }

    /**
     * Creates a data URI from a file
     * @param string|\ride\library\system\file\File $file
     * @return ride\library\http\DataUri
     */
    public function createDataUriFromFile($file) {
        if (!$file instanceof File) {
            $file = $this->fileBrowser->getFile($file);
            if (!$file) {
                return null;
            }
        }

        $mime = $this->mimeSniffer->getMediaTypeForFile($file);

        return $this->createDataUri($file->read(), $mime, null, true);
    }

}
