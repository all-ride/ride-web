<?php

namespace ride\web\mvc\controller;

use Exception;
use ride\library\http\Header;
use ride\library\http\Request;
use ride\library\http\Response;
use ride\library\system\file\browser\FileBrowser;
use ride\library\system\file\File;
use ride\service\MimeService;
use ride\web\mvc\view\FileView;

/**
 * Controller to host files from a directory
 */
class FileController extends AbstractController
{
    /**
     * Constructs a new file controller
     *
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser
     * @param \ride\service\MimeService $mimeService
     * @param \ride\library\system\file\File $path
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, MimeService $mimeService, File $path)
    {
        $this->fileBrowser = $fileBrowser;
        $this->mimeService = $mimeService;
        $this->path = $path;
    }

    /**
     * Action to host a file. The filename is provided by the arguments as tokens
     *
     * @return null
     */
    public function indexAction()
    {
        // get the requested path of the file
        $args = func_get_args();
        $path = implode('/', $args);

        if (empty($path)) {
            // no path provided
            $this->response->setStatusCode(Response::STATUS_CODE_BAD_REQUEST);

            return;
        }

        // lookup the file
        $file = null;
        try {
            $file = $this->getFile($path);
        } catch (Exception $exception) {
            $this->getLog()->logException($exception);
        }

        // To avoid a file injection vulnerability we only return file contents of file paths that start with the public dir and application/public
        $publicDir = $this->fileBrowser->getPublicDirectory()->getAbsolutePath();
        $appPublicDir = $this->fileBrowser->getApplicationDirectory()->getChild('public')->getAbsolutePath();
        if (!$file ||
            substr($file->getAbsolutePath(), 0, strlen($publicDir)) !== $publicDir ||
            substr($file->getAbsolutePath(), 0, strlen($appPublicDir)) !== $appPublicDir) {
            // file not found, set status code
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        // potential security risk ...
        // if ($file->getExtension() == 'php') {
        // // the file is a PHP script, execute it
        // require_once($file->getAbsolutePath());

        // return;
        // }

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
        $mediaType = $this->mimeService->getMediaTypeForFile($file);
        if ($mediaType && $mediaType->getMimeType() == 'text/plain') {
            $mediaType = $this->mimeService->getMediaTypeForExtension($file->getExtension());
        }
        if (!$mediaType) {
            $mediaType = 'application/octet-stream';
        }

        $this->response->setHeader(Header::HEADER_CONTENT_TYPE, (string)$mediaType);
        $this->response->setHeader(Header::HEADER_CONTENT_LENGTH, $fileSize);

        if (!$this->request->isHead()) {
            // don't send content when this is a HEAD request
            $this->response->setView(new FileView($file));
        }
    }

    /**
     * Gets the file from the include path
     *
     * @param string $path Relative path of the file in the web directory
     * @return null|\ride\library\system\file\File
     */
    protected function getFile($path)
    {
        $plainFile = $this->path->getChild($path);

        $file = $this->fileBrowser->getFile($plainFile);
        if ($file) {
            return $file;
        }

        $encodedPath = $plainFile->getParent()->getPath() . File::DIRECTORY_SEPARATOR . urlencode(
                $plainFile->getName()
            );

        return $this->fileBrowser->getFile($encodedPath);
    }
}
