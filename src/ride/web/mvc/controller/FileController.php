<?php

namespace ride\web\mvc\controller;

use ride\library\http\Header;
use ride\library\http\Request;
use ride\library\http\Response;
use ride\library\system\file\browser\FileBrowser;
use ride\library\system\file\File;

use ride\web\mvc\view\FileView;

/**
 * Controller to host files from a directory
 */
class FileController extends AbstractController {

    /**
     * Constructs a new file controller
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser
     * @param \ride\library\system\file\File $path
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, File $path) {
        $this->fileBrowser = $fileBrowser;
        $this->path = $path;
    }

    /**
     * Action to host a file. The filename is provided by the arguments as tokens
     * @return null
     */
    public function indexAction() {
        // get the requested path of the file
        $args = func_get_args();
        $path = implode('/', $args);

        if (empty($path)) {
            // no path provided
            $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

            return;
        }

        // lookup the file
        $file = $this->getFile($path);

        if (!$file) {
            // file not found, set status code
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        if ($file->getExtension() == 'php') {
            // the file is a PHP script, execute it
            require_once($file->getAbsolutePath());

            return;
        }

        $fileModificationTime = $file->getModificationTime();
        $fileSize = $file->getSize();
        $eTag = md5($path . '-' . $fileModificationTime . '-' . $fileSize);

        $this->response->setETag($eTag);
        $this->response->setLastModified($fileModificationTime);

        if ($this->response->isNotModified($this->request)) {
            // content is not modified, stop processing
            $this->response->setNotModified();

            return;
        }

        // set content headers
        $mimeResolver = $this->dependencyInjector->get('ride\\web\\mime\\MimeResolver');
        $mime = $mimeResolver->getMimeTypeByExtension($file->getExtension());

        $this->response->setHeader(Header::HEADER_CONTENT_TYPE, $mime);
        $this->response->setHeader(Header::HEADER_CONTENT_LENGTH, $fileSize);

        if (!$this->request->isHead()) {
            // don't send content when this is a HEAD request
            $this->response->setView(new FileView($file));
        }
    }

    /**
     * Gets the file from the Zibo include path
     * @param string $path Relative path of the file in the web directory
     * @return null|\ride\library\system\file\File
     */
    protected function getFile($path) {
        $plainFile = $this->path->getChild($path);

        $file = $this->fileBrowser->getFile($plainFile);
        if ($file) {
            return $file;
        }

        $encodedPath = $plainFile->getParent()->getPath() . File::DIRECTORY_SEPARATOR . urlencode($plainFile->getName());

        return $this->fileBrowser->getFile($encodedPath);
    }

}