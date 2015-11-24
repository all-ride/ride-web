<?php

namespace ride\service;

use ride\library\router\Alias;
use ride\library\router\Router;
use ride\library\router\Route;

use ride\web\router\io\RouteContainerIO;

/**
 * Service for the routing
 */
class RouterService {

    /**
     * Constructs a new routing service
     * @param \ride\library\router\Router $router
     * @param \ride\web\router\io\RouteContainerIO $routeContainerIO
     * @return null
     */
    public function __construct(Router $router, RouteContainerIO $routeContainerIO) {
        $this->router = $router;
        $this->routeContainer = $router->getRouteContainer();
        $this->routeContainerIO = $routeContainerIO;
    }

    /**
     * Gets the URL for the provided route
     * @param string $baseUrl Base URL of the system
     * @param string $id Id of the route
     * @param array $arguments Array with the argument name as key and the
     * argument as value.
     * @param array $queryParameters Array with the query parameter name as key
     * and the parameter as value.
     * @param string $querySeparator Separator between the query parameters
     * @return string Generated URL
     */
    public function getUrl($baseUrl, $id, array $arguments = null, array $queryParameters = null, $querySeparator = '&') {
        return $this->routeContainer->getUrl($baseUrl, $id, $arguments, $queryParameters, $querySeparator);
    }

    /**
     * Routes the provided path
     * @param string $method Method of the request
     * @param string $path Path of the request
     * @param string $baseUrl Base URL of the request
     * @return \ride\library\router\RouterResult
     */
    public function route($method, $path, $baseUrl = null) {
        return $this->router->route($method, $path, $baseUrl);
    }

    /**
     * Creates a new route
     * @param string $path Path of the route
     * @param string|array $callback Callback to the action of this route
     * @param string $id Id of this route
     * @param string|array|null $allowedMethods Allowed methods for this route
     * @return \ride\library\router\Route
     */
    public function createRoute($path, $callback, $id = null, $methods = null) {
        return $this->routeContainer->createRoute($path, $callback, $id, $methods);
    }

    /**
     * Gets a route by id
     * @param string $id The id of the route
     * @return Route|null
     */
    public function getRouteById($id) {
        return $this->routeContainer->getRouteById($id);
    }

    /**
     * Gets a route by path
     * @param string $path The path of the route
     * @return Route|null
     */
    public function getRouteByPath($path) {
        return $this->routeContainer->getRouteByPath($path);
    }

    /**
     * Gets the routes from this container
     * @return array Array with the path of the route as key and an instance of
     * Route as value
     */
    public function getRoutes() {
        return $this->routeContainer->getRoutes();
    }

    /**
     * Sets a route to this container
     * @param Route $route Route to add
     * @return null
     */
    public function setRoute(Route $route) {
        $this->routeContainer->setRoute($route);
        $this->routeContainerIO->setRouteContainer($this->routeContainer);
    }

    /**
     * Removes a route from this container
     * @param Route $route Instance of the route
     * @return null
     */
    public function unsetRoute(Route $route) {
        $this->routeContainer->unsetRoute($route);
        $this->routeContainerIO->setRouteContainer($this->routeContainer);
    }

    /**
     * Creates an alias instance
     * @param string $path
     * @param string $alias
     * @param boolean $isForced
     * @return \ride\library\router\Alias
     */
    public function createAlias($path, $alias, $isForced = false) {
        return $this->routeContainer->createAlias($path, $alias, $isForced);
    }

    /**
     * Gets an alias from the route container
     * @param string $path Path of the alias
     * @return \ride\library\router\Alias|null
     */
    public function getAliasByPath($path) {
        return $this->routeContainer->getAliasByPath($path);
    }

    /**
     * Gets an alias from the route container
     * @param string $path Actual alias of the alias
     * @return \ride\library\router\Alias|null
     */
    public function getAliasByAlias($alias) {
        return $this->routeContainer->getAliasByAlias($alias);
    }

    /**
     * Gets all the route aliases from the route container
     * @return array
     */
    public function getAliases() {
        return $this->routeContainer->getAliases();
    }

    /**
     * Saves an alias from the router table
     * @param \ride\library\router\Alias $alias
     * @return null
     */
    public function setAlias(Alias $alias) {
        $this->routeContainer->setAlias($alias);
        $this->routeContainerIO->setRouteContainer($this->routeContainer);
    }

    /**
     * Deletes an alias from the router table
     * @param \ride\library\router\Alias $alias
     * @return null
     */
    public function unsetAlias(Alias $alias) {
        $this->routeContainer->unsetAlias($alias);
        $this->routeContainerIO->setRouteContainer($this->routeContainer);
    }

}
