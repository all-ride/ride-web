<?php

namespace ride\web\mime;

use ride\library\config\Config;

/**
 * MIME resolver through the configuration
 */
class ConfigMimeResolver implements MimeResolver {

    /**
     * Configuration key for the known MIME types
     * @var string
     */
    const PARAM_MIME = 'mime.';

    /**
     * Default MIME type
     * @var string
     */
    const MIME_UNKNOWN = 'application/octet-stream';

    /**
     * Instance of the configuration
     * @var \ride\library\config\Config
     */
    protected $config;

    /**
     * Constructs a new MIME
     * @param \ride\library\config\Config $config
     * @return null
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * Gets the MIME type for the provided extension
     * @param string $extension File extension
     * @return string MIME type of the file extension
     */
    public function getMimeTypeByExtension($extension) {
        if (empty($extension)) {
            return self::MIME_UNKNOWN;
        }

        return $this->config->get(self::PARAM_MIME . $extension, self::MIME_UNKNOWN);
    }

}