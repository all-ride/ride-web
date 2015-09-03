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

    /**
     * Gets the file extension for files with the provided MIME type
     * @param string $mimeType MIME type
     * @return string|boolean File extension of the MIME type, false otherwise
     */
    public function getExtensionForMimeType($mimeType);

}
