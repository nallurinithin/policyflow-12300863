<?php

namespace App\Exceptions;

use RuntimeException;

class IllegalStateTransitionException extends RuntimeException
{
    public function __construct(string $fromState, string $toState, string $reason = '')
    {
        $message = "Cannot transition policy request from '{$fromState}' to '{$toState}'.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        parent::__construct($message, 422);
    }
}
