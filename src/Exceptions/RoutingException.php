<?php 

namespace Mita\UranusSocketServer\Exceptions;

use Exception;

class RoutingException extends Exception
{
    public function __construct($message = "Routing error occurred", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
