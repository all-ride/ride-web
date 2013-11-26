<?php

namespace pallo\web;

use pallo\application\system\System;
use pallo\application\Application;

use pallo\library\dependency\DependencyInjector;
use pallo\library\event\EventManager;
use pallo\library\http\Cookie;
use pallo\library\http\Header;
use pallo\library\http\HttpFactory;
use pallo\library\log\Log;
use pallo\library\mvc\dispatcher\Dispatcher;
use pallo\library\mvc\Request;
use pallo\library\mvc\Response;
use pallo\library\router\Router;
use pallo\library\router\Route;

use \Exception;

/**
 * Pallo web application
 */
class WebApplication implements Application {

    /**
     * Default session timeout time in seconds
     * @var integer
     */
    const DEFAULT_SESSION_TIMEOUT = 1800;

    /**
     * Name of the event run when an exception occurs
     * @var string
     */
    const EVENT_EXCEPTION = 'app.exception';

    /**
     * Name of the event which is run before routing
     * @var string
     */
    const EVENT_PRE_ROUTE = 'app.route.pre';

    /**
     * Name of the event which is run after routing
     * @var string
     */
    const EVENT_POST_ROUTE = 'app.route.post';

    /**
     * Name of the event which is run before dispatching
     * @var string
     */
    const EVENT_PRE_DISPATCH = 'app.dispatch.pre';

    /**
     * Name of the event which is run after dispatching
     * @var string
     */
    const EVENT_POST_DISPATCH = 'app.dispatch.post';

    /**
     * Name of the event which is run before sending the response
     * @var string
     */
    const EVENT_PRE_RESPONSE = 'app.response.pre';

    /**
     * Name of the event which is run after sending the response
     * @var string
     */
    const EVENT_POST_RESPONSE = 'app.response.post';

    /**
     * Idle state when the Zibo is not working
     * @var string
     */
    const STATE_IDLE = 'idle';

    /**
     * State value when routing a request
     * @var string
     */
    const STATE_ROUTE = 'route';

    /**
     * State value when sending the response
     * @var string
     */
    const STATE_DISPATCH = 'dispatch';

    /**
     * State value when sending the response
     * @var string
     */
    const STATE_RESPONSE = 'response';

    /**
     * Instance of the event manager
     * @var pallo\library\event\EventManager
     */
    protected $eventManager;

    /**
     * HTTP factory to create request and response objects
     * @var pallo\library\http\HttpFactory
     */
    protected $httpFactory;

    /**
     * Router to obtain the Route object
     * @var pallo\library\router\Router
     */
    protected $router;

    /**
     * Dispatcher of the route callback
     * @var pallo\library\mvc\dispatcher\Dispatcher
     */
    protected $dispatcher;

    /**
     * Data container of the request
     * @var pallo\library\mvc\Request
     */
    protected $request;

    /**
     * Data container of the response
     * @var pallo\library\mvc\Response
     */
    protected $response;

    /**
     * Current state of this application
     * @var string
     */
    protected $state;

    /**
     * Instance of the Log
     * @var zibo\library\log\Log
     */
    protected $log;

    /**
     * Instance of the dependency injector
     * @var pallo\library\dependency\DependencyInjector
     */
    protected $dependencyInjector;

    /**
     * Session timeout in seconds
     * @var integer
     */
    protected $sessionTimeout;

    /**
     * Constructs a new web app
     * @return null
     */
    public function __construct(EventManager $eventManager, HttpFactory $httpFactory, Router $router, Dispatcher $dispatcher) {
        $this->eventManager = $eventManager;
        $this->httpFactory = $httpFactory;
        $this->router = $router;
        $this->dispatcher = $dispatcher;
        $this->request = null;
        $this->response = null;
        $this->log = null;
        $this->state = self::STATE_IDLE;
    }

    /**
     * Gets the current state
     * @return string State constant
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Sets the Log
     * @param pallo\library\log\Log $log
     * @return null
     */
    public function setLog(Log $log) {
        $this->log = $log;
    }

    /**
     * Gets the Log
     * @return pallo\library\log\Log
     */
    public function getLog() {
        return $this->log;
    }

    /**
     * Sets the dependency injector to obtain the session dynamically
     * @param DependencyInjector $dependencyInjector
     * @return null
     */
    public function setDependencyInjector(DependencyInjector $dependencyInjector) {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Sets the request
     * @param pallo\library\mvc\Request $request
     * @return null
     */
    public function setRequest(Request $request = null) {
        $this->request = $request;
    }

    /**
     * Gets the request
     * @return pallo\library\mvc\Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Gets the response
     * @return pallo\library\mvc\Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Sets the router
     * @param pallo\library\router\Router $router
     * @return null
     */
    public function setRouter(Router $router) {
        $this->router = $router;
    }

    /**
     * Gets the router
     * @return pallo\library\router\Router
     */
    public function getRouter() {
        return $this->router;
    }

    /**
     * Gets the URL of the provided route
     * @param string $routeId The id of the route
     * @param array $arguments Path arguments for the route
     * @return string
     */
    public function getUrl($routeId, array $arguments = null) {
        if (!$this->router) {
            throw new Exception('Could not get the URL for ' . $routeId . ': no router set');
        } elseif (!$this->request) {
            throw new Exception('Could not get the URL for ' . $routeId . ': no request set');
        }

        $routeContainer = $this->router->getRouteContainer();

        return $routeContainer->getUrl($this->request->getBaseScript(), $routeId, $arguments);
    }

    /**
     * Sets the dispatcher
     * @param pallo\library\mvc\dispatcher\Dispatcher $dispatcher
     * @return null
     */
    public function setDispatcher(Dispatcher $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Gets the dispatcher
     * @return pallo\library\mvc\dispatcher\Dispatcher
     */
    public function getDispatcher() {
        return $this->dispatcher;
    }

    /**
     * Services a HTTP request
     * @return null
     */
    public function service() {
        if ($this->state != self::STATE_IDLE) {
            throw new SystemException('Could not service the application: application is already being serviced');
        }

        $this->state = self::STATE_ROUTE;

        if ($this->log) {
            $start = $this->log->getTime();
        }

        if (!$this->request) {
            $this->request = $this->httpFactory->createRequestFromServer();
        }

        if ($this->dependencyInjector && method_exists($this->request, 'setDependencyInjector')) {
            $this->request->setDependencyInjector($this->dependencyInjector);
        }

        // keep the initial request
        $request = $this->request;

        if ($this->log) {
            $method = $request->getMethod();

            $this->log->logDebug('Receiving request', $method . ' ' . $request->getPath(), System::LOG_SOURCE);

            $headers = $request->getHeaders();
            foreach ($headers as $header) {
                $this->log->logDebug('Receiving header', $header, System::LOG_SOURCE);
            }

            if ($request->getBody()) {
                $this->log->logDebug('Request body', $request->getBody(), System::LOG_SOURCE);
            }
        }

        $this->response = $this->httpFactory->createResponse();

        if ($this->dependencyInjector) {
            $this->dependencyInjector->setInstance($this->request, array('pallo\\library\\http\\Request', 'pallo\\library\\mvc\\Request'));
            $this->dependencyInjector->setInstance($this->request, array('pallo\\library\\httþ\\Response', 'pallo\\library\\mvc\\Response'));
        }

        try {
            $this->route();

            $this->state = self::STATE_DISPATCH;

            if (!$this->request && $this->response->getStatusCode() == Response::STATUS_CODE_OK && !$this->response->getView() && !$this->response->getBody()) {
                // there is no request to start the dispatch, forward to the public controller
                $method = $request->getMethod();
                if ($this->dependencyInjector && ($method == Request::METHOD_GET || $method == Request::METHOD_HEAD)) {
                    $arguments = ltrim($request->getBasePath(true), '/');
                    if ($arguments) {
                        $controller = $this->dependencyInjector->get('pallo\\library\\mvc\\controller\\Controller', 'public');
                        $callback = array($controller, 'indexAction');

                        $route = new Route('/', $callback);
                        $route->setIsDynamic(true);
                        $route->setArguments(explode('/', $arguments));
                    } else {
                        $controller = $this->dependencyInjector->get('pallo\\library\\mvc\\controller\\IndexController');
                        $callback = array($controller, 'indexAction');

                        $route = new Route('/', $callback);
                    }

                    $this->request = $request;
                    $this->request->setRoute($route);
                } else {
                    $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);
                }
            }

            $this->dispatch();

            $this->setRequest($request);
        } catch (Exception $exception) {
            $this->setRequest($request);

            $this->handleException($exception);
        }

        $this->state = self::STATE_RESPONSE;

        $this->sendResponse();

        if ($this->dependencyInjector) {
            $this->dependencyInjector->unsetInstance(array('pallo\\library\\http\\Request', 'pallo\\library\\mvc\\Request'));
            $this->dependencyInjector->unsetInstance(array('pallo\\library\\http\\Response', 'pallo\\library\\mvc\\Response'));
        }

        $this->request = null;
        $this->response = null;

        if ($this->log) {
            $stop = $this->log->getTime();
            $spent = $stop - $start;

            list($seconds, $nanoSeconds) = explode('.', $spent);
            $spent = $seconds . '.' .substr($nanoSeconds, 0, 4);

            $this->log->logDebug('Service took ' . $spent . ' seconds', null, System::LOG_SOURCE);
        }

        $this->state = self::STATE_IDLE;
    }

    /**
     * Performs the routing
     * @return null
     */
    protected function route() {
        $this->eventManager->triggerEvent(self::EVENT_PRE_ROUTE, array('web' => $this));

        if (!$this->request) {
            return;
        }

        $method = $this->request->getMethod();
        $path = $this->request->getBasePath();
        $baseUrl = $this->request->getBaseUrl();

        if ($this->log) {
            $this->log->logDebug('Routing ' . $method . ' ' . $path, $baseUrl, System::LOG_SOURCE);
        }

        $routerResult = $this->router->route($method, $path, $baseUrl);
        if (!$routerResult->isEmpty()) {
            $route = $routerResult->getRoute();
            if ($route) {
                $this->request->setRoute($route);
            } else {
                $this->setRequest(null);

                $allowedMethods = $routerResult->getAllowedMethods();

                $this->response->setStatusCode(Response::STATUS_CODE_METHOD_NOT_ALLOWED);
                $this->response->addHeader(Header::HEADER_ALLOW, implode(', ', $allowedMethods));

                if ($this->log) {
                    $this->log->logDebug('Requested method ' . $method . ' not allowed', null, System::LOG_SOURCE);
                }
            }
        } else {
            $this->setRequest(null);
        }

        $this->eventManager->triggerEvent(self::EVENT_POST_ROUTE, array('web' => $this));

        if (!$this->log) {
            return;
        }

        $request = $this->getRequest();
        if ($request) {
            $route = $request->getRoute();

            $this->log->logDebug('Routed to ' . $route, null, System::LOG_SOURCE);
        } else {
            $this->log->logDebug('No route matched', null, System::LOG_SOURCE);
        }
    }

    /**
     * Dispatch the request to the action of the controller
     * @return null
     */
    protected function dispatch() {
        if (!$this->request) {
            return;
        }

        $dispatcher = $this->getDispatcher();

        // request chaining
        while ($this->request) {
            $this->eventManager->triggerEvent(self::EVENT_PRE_DISPATCH, array('web' => $this));

            if (!$this->request) {
                continue;
            }

            $chainedRequest = $dispatcher->dispatch($this->request, $this->response);

            if ($chainedRequest && !$chainedRequest instanceof Request) {
                throw new Exception('Action returned a invalid value, return nothing or a new pallo\\library\\mvc\\Request object for request chaining.');
            }

            $this->setRequest($chainedRequest);

            $this->eventManager->triggerEvent(self::EVENT_POST_DISPATCH, array('web' => $this));
        }
    }

    /**
     * Sends the response to the client
     * @return null
     */
    protected function sendResponse() {
        $this->eventManager->triggerEvent(self::EVENT_PRE_RESPONSE, array('web' => $this));

        $this->setSessionCookie();

        $this->renderView();

        // send the response
        if ($this->log) {
            $this->log->logDebug('Sending response', 'Status code ' . $this->response->getStatusCode(), System::LOG_SOURCE);

            $headers = $this->response->getHeaders();
            foreach ($headers as $header) {
                $this->log->logDebug('Sending header', $header, System::LOG_SOURCE);
            }

            $cookies = $this->response->getCookies();
            foreach ($cookies as $cookie) {
                $this->log->logDebug('Sending header', Header::HEADER_SET_COOKIE . ': ' . $cookie, System::LOG_SOURCE);
            }

            $view = $this->response->getView();
            if ($view) {
                $this->log->logDebug('Rendering and sending view', get_class($view), System::LOG_SOURCE);
            }
        }

        $this->response->send($this->request);

        $this->eventManager->triggerEvent(self::EVENT_POST_RESPONSE, array('web' => $this));

        // write the session
        if ($this->request->hasSession()) {
            $session = $this->request->getSession();
            $session->write();

            if ($this->log) {
                $this->log->logDebug('Current session:', $session->getId(), System::LOG_SOURCE);

                $variables = $session->getAll();
                ksort($variables);
                foreach ($variables as $name => $value) {
                    $this->log->logDebug('- ' . $name, var_export($value, true), System::LOG_SOURCE);
                }
            }
        } elseif ($this->log) {
            $this->log->logDebug('No session loaded', '', System::LOG_SOURCE);
        }
    }

    /**
     * Renders the view.
     *
     * <p>Render the view before sending the status code and headers. This way
     * the error handler can still create a clean response if an exception
     * occurs while rendering the view.</p>
     * @return null
     * @throws Exception When an exception occurs and no event listener is
     * registered to the exception event.
     */
    protected function renderView() {
        try {
            $view = $this->response->getView();
            if ($view) {
                if ($this->log) {
                    $this->log->logDebug('Rendering view', get_class($view), System::LOG_SOURCE);
                }

                if (!$view instanceof FileView) {
                    $body = $view->render(true);

                    $this->response->setBody($body);
                    $this->response->setView(null);
                }
            }
        } catch (Exception $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Sets the session cookie if not set
     * @return null
     */
    protected function setSessionCookie() {
        if (!$this->request->hasSession()) {
            return;
        }

        $cookieName = $this->request->getSessionCookieName();
        $session = $this->request->getSession();

        $timeout = $this->getSessionTimeout();
        if ($timeout) {
            $expires = time() + $timeout;
        } else {
            $expires = 0;
        }

        $domain = $this->request->getHeader(Header::HEADER_HOST);
        $path = str_replace($this->request->getServerUrl(), '', $this->request->getBaseUrl());
        if (!$path) {
            $path = '/';
        }

        $cookie = new Cookie($cookieName, $session->getId(), $expires, $domain, $path);
        $this->response->setCookie($cookie);
    }

    /**
     * Gets the session timeout
     * @return integer Session timeout in seconds
     */
    public function getSessionTimeout() {
        if ($this->sessionTimeout === null) {
            $this->sessionTimeout = self::DEFAULT_SESSION_TIMEOUT;
        }

        return $this->sessionTimeout;
    }

    /**
     * Sets the session timeout
     * @param integer $sessionTimeout Timeout in seconds
     * @return null
     */
    public function setSessionTimeout($sessionTimeout) {
        $this->sessionTimeout = $sessionTimeout;
    }

    /**
     * Handle a exception
     * @param Exception $exception
     * @return null
     * @throws Exception when no listeners available for the exception event
     */
    protected function handleException(Exception $exception) {
        if ($this->log) {
            $this->log->logException($exception, System::LOG_SOURCE);
        }

        if ($this->eventManager->hasEventListeners(self::EVENT_EXCEPTION)) {
            $this->eventManager->triggerEvent(self::EVENT_EXCEPTION, array('web' => $this, 'exception' => $exception));
        } else {
            throw $exception;
        }
    }

}