<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(int $requested, int $available)
    {
        parent::__construct(
            "Insufficient stock: requested {$requested}, available {$available}."
        );
    }
}
