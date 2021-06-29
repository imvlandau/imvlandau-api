<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * A wrapper for holding data to be used for a application/problem+json response
 */
class ApiProblem
{
    const TYPE_VALIDATION_ERROR = 'validation_error';
    const TYPE_INVALID_REQUEST_BODY_FORMAT = 'invalid_body_format';
    const TYPE_UNDEFINED_ARGUMENT = 'undefined_argument';

    private static $messages = array(
        self::TYPE_VALIDATION_ERROR => 'There was a validation error',
        self::TYPE_INVALID_REQUEST_BODY_FORMAT => 'Invalid JSON format sent',
        self::TYPE_UNDEFINED_ARGUMENT => 'Undefined argument or method',
    );

    private $statusCode;
    private $message;
    private $type;
    private $extraData = array();

    public function __construct($statusCode, $message = "", $type = "")
    {
        $this->statusCode = $statusCode;
        // triggered by vendors
        $this->message = array_key_exists($statusCode, Response::$statusTexts) ? Response::$statusTexts[$statusCode] : Response::$statusTexts[500];
        if (!empty($message)) {
            // triggered by app
            $this->message = $message;
        }
        $this->type = isset(self::$messages[$type]) ? $type : "about:blank";
    }

    public function toArray()
    {
        return array_merge(
            array(
                'status' => $this->statusCode,
                'message' => $this->message,
                'type' => $this->type,
            ),
            $this->extraData
        );
    }

    public function set($name, $value)
    {
        $this->extraData[$name] = $value;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
