<?php 

namespace One\Validation;

use One\Http\Response;

class ValidationException extends \Exception
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var Response
     */
    public $response = null;

    public function __construct(
        Response $response,
        array $errors = [],
        string $message = 'Error getting validation data', 
        int $code = 0, 
        \Throwable $previous = null
    )
    {
        $this->response = $response;
        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}