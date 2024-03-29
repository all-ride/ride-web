<?php

namespace ride\web\mvc\view;

use ride\library\mvc\view\View;
use ride\library\StringHelper;

use \Throwable;

/**
 * View to display an exception
 */
class ExceptionView implements View {

    /**
     * The exception to display
     * @var string
     */
    protected $exception;

    /**
     * Constructs a new exception view
     * @param Exception $exception
     * @return null
     */
    public function __construct(Throwable $exception) {
        $this->exception = $exception;
    }

    /**
     * Renders the output for this view
     * @param boolean $willReturnValue True to return the rendered view, false
     * to send it straight to the client
     * @return mixed Null when provided $willReturnValue is set to true, the
     * rendered output otherwise
     */
    public function render($willReturnValue = true) {
        $output = $this->renderHtml($this->getExceptions($this->exception));

        if ($willReturnValue) {
            return $output;
        }

        echo $output;
    }

    /**
     * Renders the HTML of the exception array
     * @param array $exception
     * @return string HTML page for the exception
     */
    protected function renderHtml(array $exceptions) {
        $output = "<!DOCTYPE html>\n";
        $output .= "<html>\n";
        $output .= "    <head>\n";
        $output .= "        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        $output .= "        <title>😢 Whoopsie!</title>\n";
        $output .= "        <style>pre { padding: 5px; background-color: #ACE3FE; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; } h1, a, .icon { color: #393E46; }</style>\n";
        $output .= "    </head>\n";
        $output .= "    <body style=\"background-color: #D8D8D8; color: #333333; font-family: sans-serif; font-size: 1.2em;\">\n";
        $output .= "        <div style=\"padding-left: 75px;\">\n";
        $output .= "            <div style=\"position: absolute; left: 20px; top: 20px; width: 50px; font-weight: bold; font-size: 2em;\" class=\"icon\">😢</div>\n";
        $output .= "			<h1 style=\"color:#FE3000\">Whoops!</h1>\n";
        $output .= "			<p>An exception is thrown and it was not caught by the system.</p>\n";
        $output .= "			<div style=\"font-size: smaller;\">\n";

        $indexLastException = count($exceptions) - 1;
        foreach ($exceptions as $index => $exception) {
            $messageLines = explode("\n", $exception['message']);
            $message = array_shift($messageLines);

            $output .= '<h3><strong>' . $message . '</strong></h3>';

            if ($messageLines) {
                $output .= '<pre style="background-color: #fff; color: #333333">';
                foreach ($messageLines as $messageLine) {
                    $output .= $messageLine . "\n";
                }
                $output .= '</pre>';
            }

            if ($index == 0) {
                $source = $this->getExceptionSource($exception['exception']);
                $fileUrl = $exception['file'];
                $line = substr(strrchr($fileUrl, ':'), 1);

                $sc = strrpos($fileUrl, ':');
                if ($sc !== false) {
                    $fileUrl = substr($fileUrl, 0, $sc);

                }

                $output .= '<p>The code:</p>';
                $output .= '<p><a href="phpstorm://open?file=' . $fileUrl . '&line='.$line.'">' . $exception['file'] . '</a></p>';
                $output .= '<pre style="background-color: #fff; color: #333333">' . htmlentities($source) . '</pre>';

                $source = null;
            }
            $output .= '<p>The trace:</p>';
            $output .= '<pre style="background-color: #fff; color: #333333">' . $exception['trace'] . '</pre>';

            if ($index != $indexLastException) {
                $output .= "<p>Causes:</p>";
            }
        };

        $output .= "            </div>\n";
        $output .= "        </div>\n";
        $output .= "    </body>\n";
        $output .= "</html>";

        return $output;
    }

    /**
     * Gets an array of the provided exception with causing exceptions
     * @param Exception $exception
     * @return array Array with Exception instances in order of cause
     */
    protected function getExceptions(Throwable $exception) {
        $exceptions = array();

        do {
            $exceptions[] = $this->getExceptionArray($exception);
            $exception = $exception->getPrevious();
        } while ($exception);

        return array_reverse($exceptions);
    }

    /**
     * Parse the exception in a structured array for easy display
     * @param Exception $exception
     * @return array Array containing the values needed to display the exception
     */
    protected function getExceptionArray(Throwable $exception) {
        $message = $exception->getMessage();

        $array = array();
        $array['message'] = get_class($exception) . (!empty($message) ? ': ' . $message : '');
        $array['file'] = $exception->getFile() . ':' . $exception->getLine();
        $array['trace'] = $exception->getTraceAsString();
        $array['exception'] = $exception;

        if ($exception instanceof ValidationException) {
            $array['message'] .= $exception->getErrorsAsString();
        }

        return $array;
    }

    /**
     * Gets the source snippet where the exception has been thrown
     * @param Exception $exception
     * @param integer $offset Number of lines before and after the thrown line
     * @return array Array containing the values needed to display the exception
     */
    protected function getExceptionSource(Throwable $exception, $offset = 5) {
        if (!file_exists($exception->getFile())) {
            return '';
        }

        $source = file_get_contents($exception->getFile());
        $source = StringHelper::addLineNumbers($source);
        $source = explode("\n", $source);

        $line = $exception->getLine();

        $offsetAfter = ceil($offset / 2);
        $offsetBefore = $offset + ($offset - $offsetAfter);

        $sourceOffset = max(0, $line - $offsetBefore);
        $sourceLength = min(count($source), $line + $offsetAfter) - $sourceOffset;

        $source = array_slice($source, $sourceOffset, $sourceLength);
        $source = implode("\n", $source);

        return $source;
    }

}
