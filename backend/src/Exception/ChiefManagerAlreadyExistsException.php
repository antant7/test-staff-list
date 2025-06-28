<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Exception thrown when trying to create a second chief manager (pid = 0)
 */
class ChiefManagerAlreadyExistsException extends ConflictHttpException
{
    public function __construct(?\Throwable $previous)
    {
        parent::__construct(
            'Главный начальник может быть только один!',
            $previous
        );
    }
}
