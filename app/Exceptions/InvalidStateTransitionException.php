<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to, string $entity = 'record')
    {
        parent::__construct("Cannot transition {$entity} from '{$from}' to '{$to}'.");
    }
}
