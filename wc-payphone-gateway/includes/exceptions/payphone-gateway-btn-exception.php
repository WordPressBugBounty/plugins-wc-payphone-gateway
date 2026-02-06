<?php
/**
 * Class Payphone_Gateway_Btn_Exception
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Payphone_Gateway_Btn_Exception extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $statusCode, $error, $code = 0, Exception $previous = null)
    {
        // some code

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);

        $this->status_code = $statusCode;
        $this->error = $error;
    }

    // custom string representation of object
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    private $status_code;

    private $error;

    public function get_error()
    {
        return $this->error;
    }

    public function get_status()
    {
        return $this->status_code;
    }

}