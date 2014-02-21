<?php

namespace ride\web\mime;

/**
 * Interface to resolve the MIME types
 */
interface MimeResolver {

    /**
     * Gets the MIME type for the provided extension
     * @param string $extension File extension
     * @return string MIME type of the file extension
     */
    public function getMimeTypeByExtension($extension);

}