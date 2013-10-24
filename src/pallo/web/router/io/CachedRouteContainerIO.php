<?php

namespace pallo\web\router\io;

use pallo\library\dependency\DependencyCallArgument;
use pallo\library\router\exception\RouterException;
use pallo\library\router\RouteContainer;
use pallo\library\system\file\File;

/**
 * Cache decorator for another RouterContainerIO. This IO will get the routes
 * from the wrapped IO and generate a PHP script to include. When the generated
 * PHP script exists, this will be used to define the container. It should be
 * faster since only 1 include is done which contains plain PHP variable
 * initialization;
 */
class CachedRouteContainerIO implements RouteContainerIO {

    /**
     * RouterContainerIO which is cached by this RouterContainerIO
     * @var pallo\web\router\io\RouterContainerIO
     */
    private $io;

    /**
     * File to write the cache to
     * @var pallo\library\system\file\File
     */
    private $file;

    /**
     * Constructs a new cached RouterContainerIO
     * @param pallo\web\router\io\RouterContainerIO $io the RouterContainerIO
     * which needs a cache
     * @param pallo\library\system\file\File $file The file for the cache
     * @return null
     */
    public function __construct(RouteContainerIO $io, File $file) {
        $this->io = $io;
        $this->setFile($file);
    }

    /**
     * Sets the file for the generated code
     * @param pallo\library\system\file\File $file File to generate the code in
     * @return null
     */
    public function setFile(File $file) {
        $this->file = $file;
    }

    /**
     * Gets the file for the generated code
     * @return pallo\library\system\file\File File to generate the code in
     * @return null
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Gets the route container from a data source
     * @return pallo\library\router\RouteContainer
     */
    public function getRouteContainer() {
        if ($this->file->exists()) {
            // the generated script exists, include it
            include $this->file->getPath();

            if (isset($container)) {
                // the script defined a container, return it
                return $container;
            }
        }
        // we have no container, use the wrapped IO to get one
        $container = $this->io->getRouteContainer();

        // generate the PHP code for the obtained container
        $php = $this->generatePhp($container);

        // make sure the parent directory of the script exists
        $parent = $this->file->getParent();
        $parent->create();

        // write the PHP code to file
        $this->file->write($php);

        // return the contianer
        return $container;
    }

    /**
     * Sets the route container to the data source
     * @param pallo\library\router\RouteContainer;
     * @return null
     */
    public function setRouteContainer(RouteContainer $container) {
        $this->io->setRouterContainer($container);

        if ($this->file->exists()) {
            $this->file->delete();
        }
    }

    /**
     * Generates a PHP source file for the provided route container
     * @param pallo\library\router\RouteContainer $container
     * @return string
     */
    protected function generatePhp(RouteContainer $container) {
        $output = "<?php\n\n";
        $output .= "/*\n";
        $output .= " * This file is generated by pallo\web\router\io\CachedRouteContainerIO.\n";
        $output .= " */\n";
        $output .= "\n";
        $output .= "use pallo\\library\\dependency\\DependencyCallArgument;\n";
        $output .= "use pallo\\library\\router\\Route;\n";
        $output .= "use pallo\\library\\router\\RouteContainer;\n";
        $output .= "\n";
        $output .= '$container' . " = new RouteContainer();\n";
        $output .= "\n";

        $routes = $container->getRoutes();
        foreach ($routes as $route) {
            $callback = $route->getCallback();

            $allowedMethods = $route->getAllowedMethods();
            if ($allowedMethods) {
                $allowedMethods = array_keys($allowedMethods);
            }

            $id = $route->getId();

            $output .= '$route = new Route(';
            $output .= var_export($route->getPath(), true) . ', ';
            $output .= var_export($callback, true) . ', ';
            $output .= var_export($id, true) . ', ';
            $output .= var_export($allowedMethods, true) . ");\n";

            if ($route->isDynamic()) {
                $output .= '$route->setIsDynamic(true);' . "\n";
            }

            $arguments = $route->getPredefinedArguments();
            if ($arguments) {
                $argumentIndex = 1;

                $predefinedArguments = 'array(';

                foreach ($arguments as $name => $argument) {
                    if (is_scalar($argument)) {
                        $predefinedArguments .= var_export($name, true) . ' => ' . var_export($argument, true) . ', ';

                        continue;
                    }

                    if (!$argument instanceof DependencyCallArgument) {
                        throw new RouterException('Invalid predefined argument for route ' . $route->getPath());
                    }

                    $output .= '$a' . $argumentIndex . ' = new DependencyCallArgument(';
                    $output .= var_export($argument->getName(), true) . ', ';
                    $output .= var_export($argument->getType(), true) . ', ';
                    $output .= var_export($argument->getProperties(), true) . ");\n";

                    $predefinedArguments .= var_export($name, true) . ' => $a' . $argumentIndex . ', ';

                    $argumentIndex++;
                }

                $output .= '$route->setPredefinedArguments(' . $predefinedArguments . "));\n";
            }

            $locale = $route->getLocale();
            if ($locale) {
                $output .= '$route->setLocale(' . var_export($locale, true) . ');' . "\n";
            }

            $baseUrl = $route->getBaseUrl();
            if ($baseUrl) {
                $output .= '$route->setBaseUrl(' . var_export($baseUrl, true) . ');' . "\n";
            }

            $output .= '$container->addRoute($route);' . "\n\n";
        }

        return $output;
    }

}