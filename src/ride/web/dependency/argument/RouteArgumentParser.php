<?php

namespace ride\web\dependency\argument;

use ride\application\dependency\argument\DependencyArgumentParser;

use ride\library\config\Config;
use ride\library\dependency\argument\ArgumentParser;
use ride\library\dependency\exception\DependencyException;
use ride\library\dependency\DependencyCallArgument;

use ride\web\WebApplication;

/**
 * Parser to get a value from the routing table; with config support.
 */
class RouteArgumentParser implements ArgumentParser {

    /**
     * Name of the property for the id of a route
     * @var string
     */
    const PROPERTY_ID = 'id';

    /**
     * Name of the property for the arguments
     * @var string
     */
    const PROPERTY_ARGUMENTS = 'arguments';

    /**
     * Instance of the web application
     * @var \ride\web\WebApplication
     */
    protected $web;

    /**
     * Instance of the configuration
     * @var \ride\library\config\Config
     */
    protected $config;

    /**
     * Constructs a new route argument parser
     * @param \ride\web\WebApplication $web
     * @param \ride\library\config\Config $config
     * @return null
     */
    public function __construct(WebApplication $web, Config $config) {
        $this->web = $web;
        $this->config = $config;
    }

    /**
     * Gets the actual value of the argument
     * @param \ride\library\dependency\DependencyCallArgument $argument
     * @return mixed Value from the routing table
     */
    public function getValue(DependencyCallArgument $argument) {
        $routeId = $argument->getProperty(self::PROPERTY_ID);
        $routeArguments = $argument->getProperty(self::PROPERTY_ARGUMENTS, array());

        if (!$routeId) {
            throw new DependencyException('No id property set for route argument $' . $argument->getName());
        } elseif (!is_array($routeArguments)) {
            throw new DependencyException('Invalid arguments provided for route argument $' . $argument->getName());
        }

        $routeId = DependencyArgumentParser::processDependencyId($routeId, $this->config);

        return $this->web->getUrl($routeId, $routeArguments);
    }

}
