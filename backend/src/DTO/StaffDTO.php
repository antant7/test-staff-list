<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for Staff entity
 * Used for API input validation and data transfer
 */
class StaffDTO
{
    #[Assert\Type('integer')]
    #[Assert\PositiveOrZero]
    public ?int $pid = null;

    #[Assert\Length(max: 100)]
    #[Assert\NotBlank(allowNull: true)]
    public ?string $firstName = null;

    #[Assert\Length(max: 100)]
    #[Assert\NotBlank(allowNull: true)]
    public ?string $lastName = null;

    #[Assert\Length(max: 150)]
    public ?string $position = null;

    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public ?string $email = null;

    #[Assert\Length(max: 20)]
    #[Assert\Regex(
        pattern: '/^[\+]?[1-9][\d\s\-\(\)]{7,20}$/',
        message: 'Phone number format is invalid'
    )]
    public ?string $homePhone = null;

    #[Assert\Length(max: 5000)]
    public ?string $notes = null;

    /**
     * Create DTO from array data
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->pid = isset($data['pid']) ? (int) $data['pid'] : null;
        $dto->firstName = $data['firstName'] ?? null;
        $dto->lastName = $data['lastName'] ?? null;
        $dto->position = $data['position'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->homePhone = $data['homePhone'] ?? null;
        $dto->notes = $data['notes'] ?? null;

        return $dto;
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'pid' => $this->pid,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'position' => $this->position,
            'email' => $this->email,
            'homePhone' => $this->homePhone,
            'notes' => $this->notes,
        ];
    }

    /**
     * Get only non-null values as array
     */
    public function toArrayFiltered(): array
    {
        return array_filter($this->toArray(), fn($value) => $value !== null);
    }
}