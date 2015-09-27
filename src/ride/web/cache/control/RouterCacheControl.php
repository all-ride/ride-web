<?php

namespace ride\web\cache\control;

use ride\application\cache\control\AbstractCacheControl;

use ride\library\config\Config;

use ride\web\router\io\CachedRouteContainerIO;
use ride\web\router\io\RouteContainerIO;

/**
 * Cache control implementation for the router
 */
class RouterCacheControl extends AbstractCacheControl {

    /**
     * Name of this control
     * @var string
     */
    const NAME = 'router';

    /**
     * Instance of the route container I/O
     * @var ride\web\router\io\RouteContainerIO
     */
    private $io;

    /**
     * Instance of the configuration
     * @var ride\library\config\Config
     */
    private $config;

    /**
     * Constructs a new event cache control
     * @param ride\web\router\io\RouteContainerIO $io
     * @param \ride\library\config\Config $config
     * @return null
     */
    public function __construct(RouteContainerIO $io, Config $config) {
        $this->io = $io;
        $this->config = $config;
    }

    /**
     * Gets whether this cache can be enabled/disabled
     * @return boolean
     */
    public function canToggle() {
        return true;
    }

    /**
     * Enables this cache
     * @return null
     */
    public function enable() {
        $io = $this->config->get('system.route.container.default');
        if ($io == 'cache') {
            return;
        }

        $this->config->set('system.route.container.cache', $io);
        $this->config->set('system.route.container.default', 'cache');
    }

    /**
     * Disables this cache
     * @return null
     */
    public function disable() {
        $io = $this->config->get('system.route.container.default');
        if ($io != 'cache') {
            return;
        }

        $io = $this->config->get('system.route.container.cache');

        $this->config->set('system.route.container.default', $io);
        $this->config->set('system.route.container.cache', null);
    }

    /**
     * Gets whether this cache is enabled
     * @return boolean
     */
    public function isEnabled() {
        return $this->io instanceof CachedRouteContainerIO;
    }

    /**
     * Warms up the cache
     * @return null
     */
    public function warm() {
        if ($this->isEnabled()) {
            $this->io->warmCache();
        }
    }

    /**
     * Clears this cache
     * @return null
     */
    public function clear() {
        if ($this->isEnabled()) {
            $this->io->clearCache();
        }
    }

}
