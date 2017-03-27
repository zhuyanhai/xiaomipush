<?php
/**
 * 通知异常类
 */

namespace XMPush\Exceptions;

use Exception;

class NotificationException extends Exception
{
    /**
     * ServiceException constructor.
     *
     * @param string $response
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($response = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($response, $code, $previous);
    }
}