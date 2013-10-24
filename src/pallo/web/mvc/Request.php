<?php

namespace pallo\web\mvc;

use pallo\library\dependency\DependencyInjector;
use pallo\library\mvc\exception\MvcException;
use pallo\library\mvc\Request as LibRequest;

/**
 * A extension of the MVC request for automatic session handling
 */
class Request extends LibRequest {

    /**
     * Default name for the cookie of the session id
     * @var string
     */
    const DEFAULT_SESSION_COOKIE = 'sid';

    /**
     * Instance of the dependency injector
     * @var pallo\library\dependency\DependencyInjector
     */
    protected $dependencyInjector;

    /**
     * Name of the cookie for the session id
     * @var string
     */
    protected $sessionCookieName;

    /**
     * Sets the dependency injector to obtain the session dynamically
     * @param DependencyInjector $dependencyInjector
     * @return null
     */
    public function setDependencyInjector(DependencyInjector $dependencyInjector) {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Sets the cookie name for the session id
     * @param string $sessionCookieName
     * @return null
     */
    public function setSessionCookieName($sessionCookieName) {
        $this->sessionCookieName = $sessionCookieName;
    }

    /**
     * Gets the cookie name for the session id
     * @return string
     */
    public function getSessionCookieName() {
        if (!$this->sessionCookieName) {
            $this->sessionCookieName = self::DEFAULT_SESSION_COOKIE;
        }

        return $this->sessionCookieName;
    }

    /**
     * Checks if a session has been set
     * @return boolean
     */
    public function hasSession() {
        $sessionCookieName = $this->getSessionCookieName();

        return $this->session !== null || $this->getCookie($sessionCookieName) || $this->getQueryParameter($sessionCookieName);
    }

    /**
     * Gets the session container
     * @return pallo\library\http\session\Session
     */
    public function getSession() {
        if ($this->session) {
            return $this->session;
        }

        $sessionCookieName = $this->getSessionCookieName();

        $sessionId = $this->getCookie($sessionCookieName);
        if (!$sessionId) {
            $sessionId = $this->getQueryParameter($sessionCookieName);
        }

        if (!$sessionId) {
            $sessionId = md5(time() . rand(100000, 999999));
        }

        if (!$this->dependencyInjector) {
            throw new MvcException('Could not get the session: dependency injector not set to the request');
        }

        $this->session = $this->dependencyInjector->get('pallo\\library\\http\\session\\Session');
        $this->session->read($sessionId);

        return $this->session;
    }

}