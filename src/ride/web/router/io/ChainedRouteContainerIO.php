<?php

namespace ride\web\router\io;

use ride\library\router\exception\RouterException;
use ride\library\router\RouteContainer;

/**
 * Chain of RouteContainerIO instances
 */
class ChainedRouteContainerIO implements RouteContainerIO {

    /**
     * Chained route container IO's
     * @var array
     */
    private $io;

    /**
     * Adds a route container IO to this chain
     * @param RouteContainerIO $routeContainerIO
     * @return null
     */
    public function addRouteContainerIO(RouteContainerIO $routeContainerIO) {
        $this->io[] = $routeContainerIO;
    }

    /**
     * Removes the provided route container IO from the chain
     * @param RouteContainerIO $routeContainerIO
     * @return null
     */
    public function removeRouteContainerIO(RouteContainerIO $routeContainerIO) {
        foreach ($this->io as $index => $io) {
            if ($routeContainerIO === $io) {
                unset($this->io[$index]);
            }
        }
    }

    /**
     * Gets the route container from a data source
     * @return \ride\library\router\RouteContainer
     */
    public function getRouteContainer() {
        $container = new RouteContainer();

        foreach ($this->io as $io) {
            $container->addContainer($io->getRouteContainer());
        }

        return $container;
    }

    /**
     * Sets the route container on the first route container IO of the chain
     * @param ride\library\router\RouteContainer $routeContainer
     * @return null
     */
    public function setRouteContainer(RouteContainer $routeContainer) {
        $io = reset($this->io);
        if (!$io) {
            throw new RouterException('Could not set the provided route container: chain is empty');
        }

        $io->setRouteContainer($routeContainer);
    }

}
