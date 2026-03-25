<?php

namespace App\Exceptions;

use RuntimeException;

class StaleModelException extends RuntimeException
{
    public function __construct(string $message = 'The record has been modified by another user. Please refresh and try again.')
    {
        parent::__construct($message);
    }
}
