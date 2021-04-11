<?php


namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpExceptionWithErrorCode extends HttpException {
    private $errorCode;

    public function __construct($httpCode, $errorCode) {
        $this->errorCode = $errorCode;

        parent::__construct($httpCode);
    }

    public function getErrorCode() {
        return $this->errorCode;
    }
}
