<?php

namespace ride\web\http\session\io;

use ride\library\http\exception\HttpException;
use ride\library\http\session\io\SessionIO;
use ride\library\log\Log;
use ride\library\system\file\File;

/**
 * File implementation for the session input/output
 */
class FileSessionIO implements SessionIO {

    /**
     * Path to save the sessions to
     * @var \ride\library\system\file\File
     */
    protected $path;

    /**
     * Timeout of the for the sessions in seconds
     * @var integer
     */
    protected $timeout;

    /**
     * Instance of the log
     * @var \ride\library\log\Log
     */
    protected $log;

    /**
     * Constructs a new file session IO
     * @param \ride\library\system\file\File $path Path for the session data
     * @param integer $timeout Timeout in seconds
     * @return null
     */
    public function __construct(File $path, $timeout) {
        $this->setPath($path);
        $this->setTimeout($timeout);
    }

    /**
     * Sets the path for the session data
     * @param \ride\library\system\file\File $path Path for the session data
     * @return null
     */
    public function setPath(File $path) {
        $this->path = $path;
    }

    /**
     * Gets the path for the session data
     * @return \ride\library\system\file\File
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Sets the timeout of the sessions
     * @param integer $timeout Timeout in seconds
     * @return null
     * @throws \ride\library\http\exception\HttpException When a invalid
     * timeout has been provided
     */
    public function setTimeout($timeout) {
        if (!is_numeric($timeout) || $timeout < 0) {
            throw new HttpException('Could not set session timeout: provided timeout is not zero or a positive integer');
        }

        $this->timeout = $timeout;
    }

    /**
     * Gets the timeout of the sessions
     * @return integer Timeout in seconds
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * Sets the log to this IO
     * @param \ride\library\log\Log $log
     * @return null
     */
    public function setLog(Log $log) {
        $this->log = $log;
    }

    /**
     * Cleans up the sessions which are invalidated
     * @param boolean $force Set to true to clear all sessions
     * @return null
     */
    public function clean($force = false) {
        $expires = time() - $this->timeout;

        $sessions = $this->path->read();
        foreach ($sessions as $session) {
            if (!$force && $session->getModificationTime() > $expires) {
                continue;
            }

            $session->delete();
        }
    }

    /**
     * Reads the session data for the provided id
     * @param string $id Id of the session
     * @return array Array with the session data
     */
    public function read($id) {
        $file = $this->path->getChild($id);
        if (!$file->exists()) {
            if ($this->log) {
                $this->log->logDebug('Reading session ' . $id, 'session does not exist', 'web');
            }

            return array();
        }

        $expires = time() - $this->timeout;

        if ($file->getModificationTime() < $expires) {
            if ($this->log) {
                $this->log->logDebug('Reading session ' . $id, 'session has expired', 'web');
            }

            $file->delete();

            return array();
        }

        $serialized = $file->read();

        $data = unserialize($serialized);
        if ($data === false) {
            if ($this->log) {
                $this->log->logError('Reading session ' . $id, 'session could not be unserialized', 'web');
            }

            $data = array();
        } elseif ($this->log) {
            $this->log->logDebug('Reading session ' . $id, null, 'web');
        }

        return $data;
    }

    /**
     * Writes the session data to storage
     * @param string $id Id of the session
     * @param array $data Session data
     * @return null
     */
    public function write($id, array $data) {
        $file = $this->path->getChild($id);

        if ($data) {
            $serialized = serialize($data);

            $parent = $file->getParent();
            $parent->create();

            $file->write($serialized);
        } elseif ($file->exists()) {
            $file->delete();
        }
    }

}
