<?php

namespace ride\web\mvc\dispatcher;

use ride\application\system\System;

use ride\library\decorator\Decorator;
use ride\library\mvc\dispatcher\GenericDispatcher;
use ride\library\log\Log;

/**
 * Dispatcher with log support
 */
class LoggedDispatcher extends GenericDispatcher {

    /**
     * Instance of the log
     * @var \ride\library\log\Log
     */
    protected $log;

    /**
     * Decorator for logged values
     * @var \ride\library\decorator\Decorator
     */
    protected $valueDecorator;

    /**
     * Sets the log
     * @param \ride\library\log\Log $log
     * @return null
     */
    public function setLog(Log $log) {
        $this->log = $log;
    }

    /**
     * Gets the dependency injector
     * @return \ride\library\log\Log|null
     */
    public function getLog(Log $log) {
        return $this->log;
    }

    /**
     * Sets the value decorator for logged values
     * @param ride\library\decorator\Decorator
     * @return null
     */
    public function setValueDecorator(Decorator $valueDecorator) {
        $this->valueDecorator = $valueDecorator;
    }

    /**
     * Gets the value decorator for logged values
     * @return \ride\library\decorator\Decorator
     */
    public function getValueDecorator() {
        return $this->valueDecorator;
    }

    /**
     * Invokes and logs the callback
     * @return mixed Return value of the callback
     */
    protected function invokeCallback() {
        if (!$this->log) {
            return parent::invokeCallback();
        }

        if ($this->valueDecorator) {
            $arguments = $this->valueDecorator->decorate($this->arguments);
        } else {
            $arguments = '[...]';
        }

        $controller = $this->callback->getClass();
        if (!$controller) {
            $this->log->logDebug('Invoking ' . $this->callback->getMethod(), $arguments, System::LOG_SOURCE);

            return $this->invoker->invoke($this->callback, $this->arguments, $this->route->isDynamic());
        }

        $returnValue = null;

        $controllerClass = get_class($controller);

        if (!$this->isController) {
            $this->log->logDebug('Invoking ' . $controllerClass . '->' . $this->callback->getMethod(), $arguments, System::LOG_SOURCE);

            return $this->invoker->invoke($this->callback, $this->arguments, $this->route->isDynamic());
        }

        $this->log->logDebug('Invoking ' . $controllerClass . '->preAction', null, System::LOG_SOURCE);
        if ($controller->preAction()) {
            $this->log->logDebug('Invoking ' . $controllerClass . '->' . $this->callback->getMethod(), $arguments, System::LOG_SOURCE);
            $returnValue = $this->invoker->invoke($this->callback, $this->arguments, $this->route->isDynamic());

            $this->log->logDebug('Invoking ' . $controllerClass . '->postAction', null, System::LOG_SOURCE);
            $controller->postAction();
        } else {
            $this->log->logDebug('Skipping ' . $controllerClass . '->' . $this->callback->getMethod(), 'preAction returned false', System::LOG_SOURCE);
        }

        return $returnValue;
    }

}