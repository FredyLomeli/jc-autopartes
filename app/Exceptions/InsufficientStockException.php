<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        string $message = 'No hay suficiente existencia disponible para completar esta operación.',
        public readonly ?int $productId = null,
        public readonly ?int $requested = null,
        public readonly ?int $available = null
    ) {
        parent::__construct($message);
    }
}
