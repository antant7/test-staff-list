<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Exception thrown when staff validation fails
 */
class StaffValidationException extends BadRequestHttpException
{
    private ConstraintViolationListInterface $violations;

    public function __construct(
        ConstraintViolationListInterface $violations,
        \Throwable $previous = null
    ) {
        $this->violations = $violations;
        
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }
        
        parent::__construct(
            'Validation failed: ' . implode(', ', $messages),
            $previous
        );
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    /**
     * Get violations as array for API response
     */
    public function getViolationsArray(): array
    {
        $errors = [];
        foreach ($this->violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        return $errors;
    }
}