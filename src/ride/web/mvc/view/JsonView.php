<?php

namespace ride\web\mvc\view;

use ride\library\mvc\view\View;

/**
 * View for a JSON response
 */
class JsonView implements View {

    /**
     * Value to be encoded to JSON
     * @var mixed
     */
    private $value;

    /**
     * Options for the json_encode function
     * @var integer
     */
    private $options;

    /**
     * Constructs a new JSON view
     * @param mixed $value Value to be encoded to JSON
     * @param integer $options Options for the json_encode function
     * @return null
     * @see json_encode
     */
    public function __construct($value, $options = JSON_PRETTY_PRINT) {
        $this->value = $value;
        $this->options = $options;
    }

    /**
     * Gets the value of this view
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Renders the output for this view by encoding the value into JSON
     * @param boolean $willReturnValue True to return the rendered view, false
     * to send it straight to the client
     * @return mixed Null when provided $willReturnValue is set to true, the
     * rendered output otherwise
     */
    public function render($willReturnValue = true) {
        $encoded = json_encode($this->value, $this->options);

        if ($willReturnValue) {
            return $encoded;
        }

        echo $encoded;
    }

}
