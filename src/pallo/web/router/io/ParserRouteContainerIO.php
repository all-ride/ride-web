<?php

namespace pallo\web\router\io;

use pallo\library\config\io\AbstractIO;
use pallo\library\config\parser\Parser;
use pallo\library\dependency\DependencyCallArgument;
use pallo\library\router\exception\RouterException;
use pallo\library\router\RouteContainer;
use pallo\library\router\Route;
use pallo\library\system\file\browser\FileBrowser;
use pallo\library\system\file\File;

/**
 * XML implementation of the RouterIO
 */
class ParserRouteContainerIO extends AbstractIO implements RouteContainerIO {

    /**
     * Parser for the configuration files
     * @var pallo\library\config\parser\Parser
     */
    protected $parser;

    /**
     * Loaded route container
     * @var pallo\library\router\RouteContainer
     */
    protected $routeContainer;

    /**
     * Constructs a new route container IO
     * @param pallo\library\system\file\browser\FileBrowser $fileBrowser
     * @param pallo\library\config\parser\Parser $parser
     * @param string $file
     * @param string $path
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, Parser $parser, $file, $path = null) {
        parent::__construct($fileBrowser, $file, $path);

        $this->parser = $parser;
        $this->routeContainer = null;
    }

    /**
     * Gets the route container
     * @return pallo\library\router\RouteContainer
     */
    public function getRouteContainer() {
        if (!$this->routeContainer) {
            $this->readContainer();
        }

        return $this->routeContainer;
    }

    /**
     * Reads the containers from the data source
     * @return null
     */
    protected function readContainer() {
        $this->routeContainer = new RouteContainer();

        $path = null;
        if ($this->path) {
            $path = $this->path . File::DIRECTORY_SEPARATOR;
        }

        $files = array_reverse($this->fileBrowser->getFiles($path . $this->file));
        foreach ($files as $file) {
            $this->readContainerFromFile($this->routeContainer, $file);
        }

        if ($this->environment) {
            $path .= $this->environment . File::DIRECTORY_SEPARATOR;

            $files = array_reverse($this->fileBrowser->getFiles($path . $this->file));
            foreach ($files as $file) {
                $this->readContainerFromFile($this->routeContainer, $file);
            }
        }
    }

    /**
     * Reads the aliases from the provided file
     * @param pallo\library\router\RouteContainer $routeContainer
     * @param pallo\library\system\file\File $file
     * @return null
     */
    protected function readContainerFromFile(RouteContainer $routeContainer, File $file) {
        try {
            $content = $file->read();
            $content = $this->parser->parseToPhp($content);

            if (!isset($content['routes'])) {
                return;
            }

            foreach ($content['routes'] as $routeStruct) {
                $routeContainer->addRoute($this->parseRouteFromStruct($routeStruct));
            }
        } catch (Exception $exception) {
            throw new DependencyException('Could not read routes from ' . $file, 0, $exception);
        }
    }

    /**
     * Parses a route object from a route struct
     * @param array $routeStruct Structure with the route data
     * @return pallo\library\router\Route
     */
    protected function parseRouteFromStruct(array $routeStruct) {
        if (!isset($routeStruct['path'])) {
            throw new RouterException('Could not parse route structure: no path set');
        }

        $path = $this->processParameter($routeStruct['path']);
        unset($routeStruct['path']);

        if (isset($routeStruct['function'])) {
            $callback = $this->processParameter($routeStruct['function']);
            unset($routeStruct['function']);
        } elseif (isset($routeStruct['controller'])) {
            $controller = $this->processParameter($routeStruct['controller']);
            unset($routeStruct['controller']);

            if (isset($routeStruct['action'])) {
                $action = $this->processParameter($routeStruct['action']);
                unset($routeStruct['action']);
            } else {
                $action = 'indexAction';
            }

            $callback = array($controller, $action);
        }

        if (isset($routeStruct['id'])) {
            $id = $this->processParameter($routeStruct['id']);
            unset($routeStruct['id']);
        } else {
            $id = null;
        }

        if (isset($routeStruct['methods'])) {
            $allowedMethods = $routeStruct['methods'];
            unset($routeStruct['methods']);
        } else {
            $allowedMethods = null;
        }

        $route = new Route($path, $callback, $id, $allowedMethods);

        if (isset($routeStruct['dynamic'])) {
            $route->setIsDynamic($this->processParameter($routeStruct['dynamic']));
            unset($routeStruct['dynamic']);
        }

        if (isset($routeStruct['locale'])) {
            $route->setLocale($this->processParameter($routeStruct['locale']));
            unset($routeStruct['locale']);
        }

        if (isset($routeStruct['base'])) {
            $route->setBaseUrl($this->processParameter($routeStruct['base']));
            unset($routeStruct['base']);
        }

        $arguments = $this->parseArgumentsFromRouteStruct($routeStruct);
        if ($arguments) {
            $route->setPredefinedArguments($arguments);
        }

        if ($routeStruct) {
            throw new RouterException('Could not add route for ' . $path . ': provided properties are invalid (' . implode(', ', array_keys($routeStruct)) . ')');
        }

        return $route;
    }

    /**
     * Gets the arguments from a route structure
     * @param array $routeStruct
     * @return null|array
     */
    protected function parseArgumentsFromRouteStruct(array &$routeStruct) {
        if (!isset($routeStruct['arguments'])) {
            return null;
        }

        foreach ($routeStruct['arguments'] as $argumentStruct) {
            if (!isset($argumentStruct['name'])) {
                throw new RouterException('Could not parse route argument: no name set');
            } else {
                $name = $argumentStruct['name'];
                unset($argumentStruct['name']);
            }

            if (!isset($argumentStruct['type'])) {
                throw new RouterException('Could not parse route argument: no type set');
            } else {
                $type = $argumentStruct['type'];
                unset($argumentStruct['type']);
            }

            if (isset($argumentStruct['properties'])) {
                $properties = $argumentStruct['properties'];
                unset($argumentStruct['name']);
            } else {
                $properties = array();
            }

            $arguments[$name] = new DependencyCallArgument($name, $type, $properties);
        }

        unset($routeStruct['arguments']);

        return $arguments;
    }

    /**
     * Sets the route container to the data source
     * @param pallo\library\router\RouteContainer $container The container to write
     * @return null
     */
    public function setRouteContainer(RouteContainer $container) {
        $path = '';
        if ($this->path) {
            $path .= $this->path . File::DIRECTORY_SEPARATOR;
        }

        $parserFile = $this->fileBrowser->getApplicationDirectory()->getChild($path . $this->file);

        // read the current routes not defined in application
        $moduleRouteContainer = new RouteContainer();

        $files = array_reverse($this->fileBrowser->getFiles($path . $this->file));
        foreach ($files as $file) {
            if (strpos($file->getPath(), $parserFile->getPath()) !== false) {
                continue;
            }

            $this->readContainerFromFile($moduleRouteContainer, $file);
        }

        // filter the routes which are not defined in application
        $moduleRoutes = $moduleRouteContainer->getRoutes();
        $routes = $container->getRoutes();
        foreach ($routes as $index => $route) {
            foreach ($moduleRoutes as $moduleRoute) {
                if ($moduleRoute == $route) {
                    unset($routes[$index]);
                }
            }
        }

        if (!$routes) {
            // no routes left to write
            if ($parserFile->exists()) {
                $parserFile->delete();
            }

            return;
        }

        // write the routes
        $parserRoutes = array();
        foreach ($routes as $route) {
            $parserRoutes[] = $this->parseStructFromRoute($route);
        }

        $content = $this->parser->parseFromPhp(array('routes' => $parserRoutes));

        $parserFile->write($content);
    }

    /**
     * Parses a route into a structure
     * @param pallo\library\router\Route $route
     * @return array
     * @throws pallo\library\router\exception\RouterException when an invalid
     * argument is set to the route
     */
    protected function parseStructFromRoute(Route $route) {
        $callback = $route->getCallback();
        $id = $route->getId();
        $allowedMethods = $route->getAllowedMethods();
        $predefinedArguments = $route->getPredefinedArguments();
        $locale = $route->getLocale();
        $baseUrl = $route->getBaseUrl();

        $routeStruct = array(
        	'path' => $route->getPath(),
        );

        if (is_string($callback)) {
            $routeStruct['function'] = $callback;
        } else {
            $routeStruct['controller'] = $callback[0];
            $routeStruct['action'] = $callback[1];
        }

        if ($id) {
            $routeStruct['id'] = $id;
        }

        if ($allowedMethods) {
            $routeStruct['methods'] = array_keys($allowedMethods);
        }

        if ($route->isDynamic()) {
            $routeStruct['dynamic'] = true;
        }

        if ($locale) {
            $routeStruct['locale'] = $locale;
        }

        if ($baseUrl) {
            $routeStruct['base'] = $baseUrl;
        }

        if ($predefinedArguments) {
            $routeStruct['arguments'] = array();

            foreach ($predefinedArguments as $argument) {
                if (!$argument instanceof DependencyCallArgument) {
                    throw new RouterException('Invalid predefined argument for route ' . $route->getPath());
                }

                $argumentStruct = array(
                    'name' => $argument->getName(),
                    'type' => $argument->getType(),
                    'properties' => $argument->getProperties(),
                );

                $routeStruct['arguments'][] = $argumentStruct;
            }
        }

        return $routeStruct;
    }

}