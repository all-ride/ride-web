<?php

namespace ride\web\mvc\dispatcher;

use ride\library\config\Config;
use ride\library\dependency\DependencyInjector;
use ride\library\reflection\Callback;

use ride\web\mvc\controller\AbstractController;

/**
 * Dispatcher with dependency injection and log support
 */
class DependencyDispatcher extends LoggedDispatcher {

    /**
     * Separator between controller class name and the dependency id
     * @var string
     */
    const SEPARATOR_CONTROLLER_DEPENDENCY = '#';

    /**
     * Instance of the configuration
     * @var \ride\library\config\Config
     */
    protected $config;

    /**
     * Instance of the dependency injector
     * @var \ride\library\dependency\DependencyInjector
     */
    protected $dependencyInjector;

    /**
     * Sets the configuration
     * @param \ride\library\config\Config $config
     * @return null
     */
    public function setConfig(Config $config) {
        $this->config = $config;
    }

    /**
     * Sets the dependency injector
     * @param \ride\library\dependency\DependencyInjector $dependencyInjector
     * @return null
     */
    public function setDependencyInjector(DependencyInjector $dependencyInjector) {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Processes the callback
     * @param Callback $callback Callback to process
     * @return Callback Processed callback
     */
    protected function processCallback(Callback $callback) {
        $interface = $callback->getClass();

        if (!$interface || !$this->dependencyInjector || !is_string($interface)) {
            return $callback;
        }

        $positionSeparator = strpos($interface, self::SEPARATOR_CONTROLLER_DEPENDENCY);
        if ($positionSeparator !== false) {
            list($interface, $id) = explode(self::SEPARATOR_CONTROLLER_DEPENDENCY, $interface, 2);
        } else {
            $id = null;
        }

        $controller = $this->dependencyInjector->get($interface, $id);

        return new Callback(array($controller, $callback->getMethod()));
    }

    /**
     * Prepares the callback
     * @return null
     */
    protected function prepareCallback() {
        parent::prepareCallback();

        $class = $this->callback->getClass();
        if (!$class || !$class instanceof AbstractController) {
            return;
        }

        $class->setConfig($this->config);
        $class->setDependencyInjector($this->dependencyInjector);
    }

}