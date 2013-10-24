<?php

namespace pallo\web\router\io;

use pallo\library\router\RouteContainer;

/**
 * Interface to obtain the container for the router
 */
interface RouteContainerIO {

    /**
     * Gets the route container from a data source
     * @return pallo\library\router\RouteContainer
     */
    public function getRouteContainer();

    /**
     * Sets the route container to the data source
     * @param pallo\library\router\RouteContainer;
     * @return null
     */
    public function setRouteContainer(RouteContainer $container);

}