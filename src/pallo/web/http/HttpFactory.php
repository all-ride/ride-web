<?php

namespace pallo\web\http;

use pallo\library\http\exception\HttpException;
use pallo\library\http\HttpFactory as LibHttpFactory;

use pallo\web\mvc\Request;

/**
 * Factory for HTTP objects
 */
class HttpFactory extends LibHttpFactory {

    /**
     * Name of the session cookie variable
     * @var string
     */
    protected $sessionCookieName;

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

        if ($this->sessionCookieName && $request instanceof Request) {
            $request->setSessionCookieName($this->sessionCookieName);
        }

        return $request;
    }

    /**
     * Creates a request from the $_SERVER variable
     * @return Request
     */
    public function createRequestFromServer() {
        $request = parent::createRequestFromServer();

        if ($this->sessionCookieName && $request instanceof Request) {
            $request->setSessionCookieName($this->sessionCookieName);
        }

        return $request;
    }

}