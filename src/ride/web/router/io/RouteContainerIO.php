<?php

namespace ride\web\router\io;

use ride\library\router\RouteContainer;

/**
 * Interface to obtain the container for the router
 */
interface RouteContainerIO {

    /**
     * Gets the route container from a data source
     * @return \ride\library\router\RouteContainer
     */
    public function getRouteContainer();

    /**
     * Sets the route container to the data source
     * @param ride\library\router\RouteContainer;
     * @return null
     */
    public function setRouteContainer(RouteContainer $container);

}
