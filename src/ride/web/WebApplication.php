<?php

namespace ride\web;

use ride\application\system\System;
use ride\application\Application;

use ride\library\decorator\Decorator;
use ride\library\dependency\DependencyInjector;
use ride\library\event\EventManager;
use ride\library\http\Cookie;
use ride\library\http\Header;
use ride\library\http\HttpFactory;
use ride\library\log\Log;
use ride\library\mvc\dispatcher\Dispatcher;
use ride\library\mvc\Request;
use ride\library\mvc\Response;

use ride\service\RouterService;

use \Exception;

/**
 * Ride web application
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
     * @var \ride\library\event\EventManager
     */
    protected $eventManager;

    /**
     * HTTP factory to create request and response objects
     * @var \ride\library\http\HttpFactory
     */
    protected $httpFactory;

    /**
     * Router to obtain the Route object
     * @var \ride\service\RouterService
     */
    protected $routerService;

    /**
     * Dispatcher of the route callback
     * @var \ride\library\mvc\dispatcher\Dispatcher
     */
    protected $dispatcher;

    /**
     * Data container of the request
     * @var \ride\library\mvc\Request
     */
    protected $request;

    /**
     * Data container of the response
     * @var \ride\library\mvc\Response
     */
    protected $response;

    /**
     * Current state of this application
     * @var string
     */
    protected $state;

    /**
     * Instance of the Log
     * @var \ride\library\log\Log
     */
    protected $log;

    /**
     * Decorator for log values
     * @var \ride\library\decorator\Decorator
     */
    protected $valueDecorator;

    /**
     * Instance of the dependency injector
     * @var \ride\library\dependency\DependencyInjector
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
    public function __construct(EventManager $eventManager, HttpFactory $httpFactory, RouterService $routerService, Dispatcher $dispatcher) {
        $this->eventManager = $eventManager;
        $this->httpFactory = $httpFactory;
        $this->routerService = $routerService;
        $this->dispatcher = $dispatcher;
        $this->request = null;
        $this->response = null;
        $this->log = null;
        $this->valueDecorator = null;
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
     * @param \ride\library\log\Log $log
     * @return null
     */
    public function setLog(Log $log) {
        $this->log = $log;
    }

    /**
     * Gets the Log
     * @return \ride\library\log\Log
     */
    public function getLog() {
        return $this->log;
    }

    /**
     * Sets the value decorator used to log values
     * @param \ride\library\decorator\Decorator $decorator
     * @return null
     */
    public function setValueDecorator(Decorator $valueDecorator) {
        $this->valueDecorator = $valueDecorator;
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
     * @param \ride\library\mvc\Request $request
     * @return null
     */
    public function setRequest(Request $request = null) {
        if ($this->dependencyInjector && method_exists($request, 'setDependencyInjector')) {
            $request->setDependencyInjector($this->dependencyInjector);
        }

        $this->request = $request;
    }

    /**
     * Gets the request
     * @return \ride\library\mvc\Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Creates a request
     * @param string $path Path for the request
     * @return \ride\library\http\Request
     */
    public function createRequest($path = null, $method = null) {
        if (!$path) {
            $request = $this->httpFactory->createRequestFromServer();
        } else {
            if ($this->request) {
                if (!$method) {
                    $method = $this->request->getMethod();
                }
                $protocol = $this->request->getProtocol();
                $headers = $this->request->getHeaders();
                $body = $this->request->getBodyParameters();
                $isSecure = $this->request->isSecure();

                $path = str_replace($this->request->getServerUrl(), '', $this->request->getBaseUrl()) . $path;
            } else {
                $protocol = null;
                $headers = null;
                $body = null;
                $isSecure = null;
            }

            $request = $this->httpFactory->createRequest($path, $method, $protocol, $headers, $body);
        }

        return $request;
    }

    /**
     * Gets the response
     * @return \ride\library\mvc\Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Gets the router service
     * @return \ride\library\router\Router
     */
    public function getRouterService() {
        return $this->routerService;
    }

    /**
     * Gets the URL for the provided route
     * @param string $routeId Id of the route
     * @param array $arguments Array with the argument name as key and the
     * argument as value.
     * @param array $queryParameters Array with the query parameter name as key
     * and the parameter as value.
     * @return string Generated URL
     */
    public function getUrl($routeId, array $arguments = null, array $queryParameters = null, $querySeparator = '&') {
        if (!$this->request) {
            throw new Exception('Could not get the URL for ' . $routeId . ': no request set');
        }

        return $this->routerService->getUrl($this->request->getBaseScript(), $routeId, $arguments, $queryParameters, $querySeparator);
    }

    /**
     * Sets the dispatcher
     * @param \ride\library\mvc\dispatcher\Dispatcher $dispatcher
     * @return null
     */
    public function setDispatcher(Dispatcher $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Gets the dispatcher
     * @return \ride\library\mvc\dispatcher\Dispatcher
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
            $this->setRequest($this->createRequest());
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
            $this->dependencyInjector->setInstance($this->request, array('ride\\library\\http\\Request', 'ride\\library\\mvc\\Request'));
            $this->dependencyInjector->setInstance($this->response, array('ride\\library\\httþ\\Response', 'ride\\library\\mvc\\Response'));
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
                        $controller = $this->dependencyInjector->get('ride\\library\\mvc\\controller\\Controller', 'public');
                        $callback = array($controller, 'indexAction');

                        $route = $this->routerService->createRoute('/', $callback);
                        $route->setIsDynamic(true);
                        $route->setArguments(explode('/', $arguments));
                    } else {
                        $controller = $this->dependencyInjector->get('ride\\library\\mvc\\controller\\IndexController');
                        $callback = array($controller, 'indexAction');

                        $route = $this->routerService->createRoute('/', $callback);
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
            $this->dependencyInjector->unsetInstance(array('ride\\library\\http\\Request', 'ride\\library\\mvc\\Request'));
            $this->dependencyInjector->unsetInstance(array('ride\\library\\http\\Response', 'ride\\library\\mvc\\Response'));
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

        $routerResult = $this->routerService->route($method, $path, $baseUrl);
        if (!$routerResult->isEmpty()) {
            $alias = $routerResult->getAlias();
            $route = $routerResult->getRoute();
            if ($alias) {
                $this->response->setRedirect($this->request->getBaseScript() . $alias->getAlias());
                $this->setRequest(null);
            } elseif ($route) {
                $this->request->setRoute($route);
            } else {
                $this->setRequest(null);

                $allowedMethods = array_keys($routerResult->getAllowedMethods());

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
        } elseif (isset($alias)) {
            $this->log->logDebug('Alias matched', $alias->getPath(), System::LOG_SOURCE);
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
                throw new Exception('Action returned a invalid value, return nothing or a new ride\\library\\mvc\\Request object for request chaining.');
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

        $this->writeSession();
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

        $session = $this->request->getSession();
        if (!$session->getAll()) {
            return;
        }

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

        $cookie = new Cookie($this->request->getSessionCookieName(), $session->getId(), $expires, $domain, $path);

        $this->response->setCookie($cookie);
    }

    /**
     * Writes the session to the data source and log the current values for debug
     * @return null
     */
    protected function writeSession() {
        // write the session
        $session = null;
        if ($this->request->hasSession()) {
            $session = $this->request->getSession();
            if ($session->isChanged()) {
                $session->write();
            }

            if (!$session->getAll()) {
                $session = null;
            }
        }

        // log the session
        if (!$this->log) {
            return;
        }

        if ($session) {
            $this->log->logDebug('Current session:', $session->getId(), System::LOG_SOURCE);

            if ($this->valueDecorator) {
                $variables = $session->getAll();

                ksort($variables);

                foreach ($variables as $name => $value) {
                    $this->log->logDebug('- ' . $name, $this->valueDecorator->decorate($value), System::LOG_SOURCE);
                }
            }
        } else {
            $this->log->logDebug('No session loaded', '', System::LOG_SOURCE);
        }
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

        $this->response->setStatusCode(Response::STATUS_CODE_SERVER_ERROR);
        $this->response->clearRedirect();

        if ($this->eventManager->hasEventListeners(self::EVENT_EXCEPTION)) {
            $this->eventManager->triggerEvent(self::EVENT_EXCEPTION, array('web' => $this, 'exception' => $exception));
        } else {
            throw $exception;
        }
    }

}
