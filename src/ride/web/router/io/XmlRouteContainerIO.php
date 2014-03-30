<?php

namespace ride\web\router\io;

use ride\application\dependency\io\XmlDependencyIO;

use ride\library\dependency\DependencyCallArgument;
use ride\library\router\exception\RouterException;
use ride\library\router\RouteContainer;
use ride\library\router\Route;
use ride\library\system\file\File;

use \DOMDocument;
use \DOMElement;
use \Exception;

/**
 * XML implementation of the RouterIO
 */
class XmlRouteContainerIO implements RouteContainerIO {

    /**
     * Path to the xml file for the routes
     * @var string
     */
    const FILE = 'routes.xml';

    /**
     * Name of the root tag
     * @var string
     */
    const TAG_ROOT = 'routes';

    /**
    * Name of the route tag
    * @var string
    */
    const TAG_ROUTE = 'route';

    /**
     * Name of the base URL attribute
     * @var string
     */
    const ATTRIBUTE_BASE = 'base';

    /**
     * Name of the path attribute
     * @var string
     */
    const ATTRIBUTE_PATH = 'path';

    /**
     * Name of the controller attribute
     * @var string
     */
    const ATTRIBUTE_CONTROLLER = 'controller';

    /**
     * Name of the action attribute
     * @var string
     */
    const ATTRIBUTE_ACTION = 'action';

    /**
     * Name of the id attribute
     * @var string
     */
    const ATTRIBUTE_ID = 'id';

    /**
     * Name of the allowed methods attribute
     * @var string
     */
    const ATTRIBUTE_ALLOWED_METHODS = 'methods';

    /**
     * Name of the dynamic attribute
     * @var string
     */
    const ATTRIBUTE_DYNAMIC = 'dynamic';

    /**
     * Name of the locale attribute
     * @var string
     */
    const ATTRIBUTE_LOCALE = 'locale';

    /**
     * Default action
     * @var string
     */
    const DEFAULT_ACTION = 'indexAction';

    /**
     * Loaded route container
     * @var ride\library\router\RouteContainer
     */
    protected $routeContainer;

    /**
     * Constructs a new XML dependency IO
     * @param \ride\library\system\file\browser\fileBrowser
     * @param string $environment
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, $path = null) {
        parent::__construct($fileBrowser, self::FILE, $path);
    }

    /**
     * Gets the route container
     * @return \ride\library\router\RouteContainer
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
     * @param \ride\library\system\file\File $file
     * @return null
     */
    protected function readContainerFromFile(RouteContainer $routeContainer, File $file) {
        $dom = new DOMDocument();
        $dom->load($file);

        $this->readRoutesFromElement($routeContainer, $file, $dom->documentElement);
    }

    /**
     * Gets the routes object from an XML routes element
     * @param \ride\library\system/file\File $file the file which is being
     * read
     * @param DomElement $routesElement the element which contains route
     * elements
     * @return null
     */
    private function readRoutesFromElement(RouteContainer $routeContainer, File $file, DOMElement $routesElement) {
        $elements = $routesElement->getElementsByTagName(self::TAG_ROUTE);
        foreach ($elements as $element) {
            $path = $this->getAttribute($file, $element, self::ATTRIBUTE_PATH);

            $controller = $this->getAttribute($file, $element, self::ATTRIBUTE_CONTROLLER);
            $action = $this->getAttribute($file, $element, self::ATTRIBUTE_ACTION, false);
            if (!$action) {
                $action = self::DEFAULT_ACTION;
            }
            $callback = array($controller, $action);

            $id = $this->getAttribute($file, $element, self::ATTRIBUTE_ID, false);
            if (!$id) {
                $id = null;
            }

            $allowedMethods = $this->getAttribute($file, $element, self::ATTRIBUTE_ALLOWED_METHODS, false);
            if ($allowedMethods) {
                $allowedMethods = explode(',', $allowedMethods);
            } else {
                $allowedMethods = null;
            }

            $route = new Route($path, $callback, $id, $allowedMethods);

            $isDynamic = $this->getAttribute($file, $element, self::ATTRIBUTE_DYNAMIC, false);
            if ($isDynamic !== '') {
                $route->setIsDynamic(in_array('1', 'true', 'yes', strtolower($isDynamic)));
            }

            $arguments = $this->readArgumentsFromRouteElement($file, $element);
            if ($arguments) {
                $route->setPredefinedArguments($arguments);
            }

            $locale = $this->getAttribute($file, $element, self::ATTRIBUTE_LOCALE, false);
            if ($locale !== '') {
                $route->setLocale($locale);
            }

            $baseUrl = $this->getAttribute($file, $element, self::ATTRIBUTE_BASE, false);
            if ($baseUrl) {
                $route->setBaseUrl($baseUrl);
            }

            $routeContainer->addRoute($route);
        }
    }

    /**
     * Gets the routes object from an XML routes element
     * @param \ride\library\system\file\File $file the file which is being
     * read
     * @param DomElement $routesElement the element which contains route
     * elements
     * @return null
     */
    private function readArgumentsFromRouteElement(File $file, DOMElement $routeElement) {
        $arguments = array();

        $argumentElements = $routeElement->getElementsByTagName(XmlDependencyIO::TAG_ARGUMENT);
        foreach ($argumentElements as $argumentElement) {
            $name = $argumentElement->getAttribute(XmlDependencyIO::ATTRIBUTE_NAME);
            $type = $argumentElement->getAttribute(XmlDependencyIO::ATTRIBUTE_TYPE);
            $properties = array();

            $propertyElements = $argumentElement->getElementsByTagName(XmlDependencyIO::TAG_PROPERTY);
            foreach ($propertyElements as $propertyElement) {
                $propertyName = $propertyElement->getAttribute(XmlDependencyIO::ATTRIBUTE_NAME);
                $propertyValue = $propertyElement->getAttribute(XmlDependencyIO::ATTRIBUTE_VALUE);

                $properties[$propertyName] = $propertyValue;
            }

            $arguments[$name] = new DependencyCallArgument($name, $type, $properties);
        }

        return $arguments;
    }

    /**
     * Gets the value of an attribute from the provided XML element
     * @param \ride\library\system\file\File $file the file which is being read
     * @param DomElement $element the element from which the attribute needs to
     * be retrieved
     * @param string $name name of the attribute
     * @param boolean $required flag to see if the value is required or not
     * @return string
     * @throws \ride\library\router\exception\RouterException when the attribute
     * is required but not set or empty
     */
    private function getAttribute(File $file, DOMElement $element, $name, $required = true) {
        $value = $element->getAttribute($name);

        if ($required && empty($value)) {
            throw new RouterException('Attribute ' . $name . ' not set in ' . $file->getPath());
        }

        return $value;
    }

    /**
     * Sets the route container to the data source
     * @param \ride\library\router\RouteContainer $container The container to write
     * @return null
     */
    public function setRouteContainer(RouteContainer $container) {
        $path = '';
        if ($this->path) {
            $path .= $this->path . File::DIRECTORY_SEPARATOR;
        }

        $xmlFile = $this->fileBrowser->getApplicationDirectory()->getChild($path . $this->file);

        // read the current routes not defined in application
        $xmlRouteContainer = new RouteContainer();

        $files = array_reverse($this->fileBrowser->getFiles($path . self::FILE));
        foreach ($files as $file) {
            if (strpos($file->getPath(), $xmlFile->getPath()) !== false) {
                continue;
            }

            $this->readContainerFromFile($xmlRouteContainer, $file);
        }

        // filter the routes which are not defined in application
        $xmlRoutes = $xmlRouteContainer->getRoutes();
        $routes = $container->getRoutes();
        foreach ($routes as $index => $route) {
            foreach ($xmlRoutes as $xmlRoute) {
                if ($xmlRoute == $route) {
                    unset($routes[$index]);
                }
            }
        }

        if (!$routes) {
            // no routes left to write
            if ($xmlFile->exists()) {
                $xmlFile->delete();
            }

            return;
        }

        // write the routes
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $routesElement = $dom->createElement(self::TAG_ROOT);
        $dom->appendChild($routesElement);

        foreach ($routes as $route) {
            $callback = $route->getCallback();
            $id = $route->getId();
            $allowedMethods = $route->getAllowedMethods();
            $predefinedArguments = $route->getPredefinedArguments();
            $locale = $route->getLocale();
            $baseUrl = $route->getBaseUrl();

            $routeElement = $dom->createElement(self::TAG_ROUTE);
            $routeElement->setAttribute(self::ATTRIBUTE_PATH, $route->getPath());
            $routeElement->setAttribute(self::ATTRIBUTE_CONTROLLER, $callback->getClass());
            $routeElement->setAttribute(self::ATTRIBUTE_ACTION, $callback->getMethod());

            if ($id !== null) {
                $routeElement->setAttribute(self::ATTRIBUTE_ID, $id);
            }

            if ($allowedMethods) {
                $routeElement->setAttribute(self::ATTRIBUTE_ALLOWED_METHODS, implode(',', $allowedMethods));
            }

            if ($route->isDynamic()) {
                $routeElement->setAttribute(self::ATTRIBUTE_DYNAMIC, 'true');
            }

            if ($predefinedArguments) {
                foreach ($predefinedArguments as $argument) {
                    if (!$argument instanceof DependencyCallArgument) {
                        throw new RouterException('Invalid predefined argument for route ' . $route->getPath());
                    }

                    $argumentElement = $dom->createElement(XmlDependencyIO::TAG_ARGUMENT);
                    $argumentElement->setAttribute(XmlDependencyIO::ATTRIBUTE_NAME, $argument->getName());
                    $argumentElement->setAttribute(XmlDependencyIO::ATTRIBUTE_TYPE, $argument->getType());

                    $properties = $argument->getProperties();
                    foreach ($properties as $key => $value) {
                        $propertyElement = $dom->createElement(XmlDependencyIO::TAG_PROPERTY);
                        $propertyElement->setAttribute(XmlDependencyIO::ATTRIBUTE_NAME, $key);
                        $propertyElement->setAttribute(XmlDependencyIO::ATTRIBUTE_VALUE, $value);

                        $argumentElement->appendChild($propertyElement);
                    }

                    $routeElement->appendChild($argumentElement);
                }
            }

            if ($locale) {
                $routeElement->setAttribute(self::ATTRIBUTE_LOCALE, $locale);
            }

            if ($baseUrl) {
                $routeElement->setAttribute(self::ATTRIBUTE_BASE, $baseUrl);
            }

            $importedRouteElement = $dom->importNode($routeElement, true);
            $routesElement->appendChild($importedRouteElement);
        }

        $dom->save($xmlFile);
    }

}