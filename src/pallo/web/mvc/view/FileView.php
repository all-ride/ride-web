<?php

namespace pallo\web\mvc\view;

use pallo\library\mvc\exception\MvcException;
use pallo\library\mvc\view\View;
use pallo\library\system\file\File;

/**
 * View to render the contents of a file
 */
class FileView implements View {

    /**
     * The file to render
     * @var zibo\library\filesystem\File
     */
    protected $file;

    /**
     * The resource to write to when rendering with the return value set
     * to false
     * @var resource
     */
    protected $handle;

    /**
     * Constructs a new file view
     * @param pallo\library\system\file\File $file File to render
     * @return null
     */
    public function __construct(File $file) {
        if (!$file->exists() || $file->isDirectory()) {
            throw new MvcException($file . ' does not exists or is a directory.');
        }

        $this->file = $file;
        $this->handle = null;
    }

    /**
     * Gets the file of this view
     * @return pallo\library\system\file\File
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Sets the passthru handle to use when this view is rendered with the
     * return value set to false
     * @param resource $handle Handle to write the file to
     * @return null
     */
    public function setPassthruHandle($handle) {
        $this->handle = $handle;
    }

    /**
     * Renders the file view
     * @param boolean $return True to return the contents of the file, false
     * to passthru the file to the output
     * @return null|string
     */
    public function render($return = true) {
        if ($return) {
            return $this->file->read();
        }

        $this->file->passthru($this->handle);
    }

}