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
        $routeContainer = new RouteContainer();

        foreach ($this->io as $io) {
            $ioRouteContainer = $io->getRouteContainer();

            $routeContainer->setRouteContainer($ioRouteContainer);

            if (!$routeContainer->getSource() && $ioRouteContainer->getSource()) {
                $routeContainer->setSource($ioRouteContainer->getSource());
            }
        }

        return $routeContainer;
    }

    /**
     * Sets the route container on the first route container IO of the chain
     * @param ride\library\router\RouteContainer $routeContainer
     * @return null
     */
    public function setRouteContainer(RouteContainer $routeContainer) {
        if (!$this->io) {
            throw new RouterException('Could not set the provided route container: chain is empty');
        }

        foreach ($this->io as $io) {
            try {
                $io->setRouteContainer($routeContainer);

                return;
            } catch (RouterException $exception) {

            }
        }

        throw new RouterException('Could not set the provided route container: no IO available in chain to save the definition');
    }

}
