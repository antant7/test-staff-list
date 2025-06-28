<?php

namespace App\Service;

use App\DTO\StaffDTO;
use App\Entity\Staff;
use App\Exception\ChiefManagerAlreadyExistsException;
use App\Exception\ParentStaffNotFoundException;
use App\Exception\StaffHasSubordinatesException;
use App\Exception\StaffNotFoundException;
use App\Exception\StaffValidationException;
use App\Repository\StaffRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StaffService
{
    private StaffRepository $staffRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(
        StaffRepository $staffRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->staffRepository = $staffRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * Create new staff member
     */
    public function createStaff(array $data): Staff
    {
        $dto = StaffDTO::fromArray($data);

        // Validate DTO
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new StaffValidationException($errors);
        }

        // Check if trying to create chief manager (pid = 0)
        if ($dto->pid === 0) {
            $existingChief = $this->staffRepository->findByPid(0);
            if ($existingChief) {
                throw new ChiefManagerAlreadyExistsException(null);
            }
        } elseif ($dto->pid !== null && $dto->pid > 0) {
            // Check if parent staff exists
            $parentStaff = $this->staffRepository->find($dto->pid);
            if (!$parentStaff) {
                throw new ParentStaffNotFoundException($dto->pid);
            }
        }

        $staff = new Staff();
        $this->populateStaffFromDTO($staff, $dto);

        $this->staffRepository->save($staff, true);

        return $staff;
    }

    /**
     * Update existing staff member
     */
    public function updateStaff(int $id, array $data): Staff
    {
        $staff = $this->staffRepository->find($id);
        if (!$staff) {
            throw new StaffNotFoundException($id);
        }

        $dto = StaffDTO::fromArray($data);

        // Validate DTO
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new StaffValidationException($errors);
        }

        // Check if trying to update to chief manager (pid = 0)
        if ($dto->pid === 0 && $staff->getPid() !== 0) {
            $existingChief = $this->staffRepository->findByPid(0);
            if ($existingChief) {
                throw new ChiefManagerAlreadyExistsException(null);
            }
        } elseif ($dto->pid !== null && $dto->pid > 0) {
            // Check if parent staff exists
            $parentStaff = $this->staffRepository->find($dto->pid);
            if (!$parentStaff) {
                throw new ParentStaffNotFoundException($dto->pid);
            }
        }

        $this->populateStaffFromDTO($staff, $dto);

        $this->staffRepository->save($staff, true);

        return $staff;
    }

    /**
     * Delete staff member
     */
    public function deleteStaff(int $id): bool
    {
        $staff = $this->staffRepository->find($id);
        if (!$staff) {
            throw new StaffNotFoundException($id);
        }

        // Check if staff member has subordinates
        if ($this->staffRepository->hasSubordinates($id)) {
            $subordinates = $this->staffRepository->findSubordinates($id);
            throw new StaffHasSubordinatesException($id, $subordinates);
        }

        $this->staffRepository->remove($staff, true);

        return true;
    }

    /**
     * Get staff member by ID
     */
    public function getStaffById(int $id): ?Staff
    {
        return $this->staffRepository->find($id);
    }

    /**
     * Get all staff members
     */
    public function getAllStaff(): array
    {
        return $this->staffRepository->findAll();
    }

    /**
     * Get staff members by position
     */
    public function getStaffByPosition(string $position): array
    {
        return $this->staffRepository->findByPosition($position);
    }

    /**
     * Search staff by name
     */
    public function searchStaffByName(string $searchTerm): array
    {
        return $this->staffRepository->searchByName($searchTerm);
    }

    /**
     * Get staff with pagination
     */
    public function getStaffWithPagination(int $page = 1, int $limit = 10): array
    {
        $staff = $this->staffRepository->findWithPagination($page, $limit);
        $totalCount = $this->staffRepository->getStaffCount();
        $totalPages = ceil($totalCount / $limit);

        return [
            'data' => $staff,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $totalCount,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    }

    /**
     * Get staff with filters and pagination
     */
    public function getStaffWithFiltersAndPagination(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $staff = $this->staffRepository->findWithFiltersAndPagination($filters, $page, $limit);
        $totalCount = $this->staffRepository->getStaffCountWithFilters($filters);
        $totalPages = ceil($totalCount / $limit);

        return [
            'data' => $staff,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $totalCount,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    }

    /**
     * Check if email is already taken
     */
    public function isEmailTaken(string $email, ?int $excludeId = null): bool
    {
        $existingStaff = $this->staffRepository->findByEmail($email);

        if (!$existingStaff) {
            return false;
        }

        // If we're updating and the email belongs to the same staff member, it's not taken
        if ($excludeId && $existingStaff->getId() === $excludeId) {
            return false;
        }

        return true;
    }

    /**
     * Convert staff entity to array
     */
    public function staffToArray(Staff $staff): array
    {
        return [
            'id' => $staff->getId(),
            'pid' => $staff->getPid(),
            'firstName' => $staff->getFirstName(),
            'lastName' => $staff->getLastName(),
            'position' => $staff->getPosition(),
            'email' => $staff->getEmail(),
            'homePhone' => $staff->getHomePhone(),
            'notes' => $staff->getNotes(),
            'createdAt' => $staff->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $staff->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Populate staff entity from DTO
     */
    private function populateStaffFromDTO(Staff $staff, StaffDTO $dto): void
    {
        if ($dto->pid !== null) {
            $staff->setPid($dto->pid);
        }

        if ($dto->firstName !== null) {
            $staff->setFirstName($dto->firstName);
        }

        if ($dto->lastName !== null) {
            $staff->setLastName($dto->lastName);
        }

        if ($dto->position !== null) {
            $staff->setPosition($dto->position);
        }

        if ($dto->email !== null) {
            $staff->setEmail($dto->email);
        }

        if ($dto->homePhone !== null) {
            $staff->setHomePhone($dto->homePhone);
        }

        if ($dto->notes !== null) {
            $staff->setNotes($dto->notes);
        }
    }

    /**
     * Validate staff data using DTO
     */
    public function validateStaffData(array $data): array
    {
        $dto = StaffDTO::fromArray($data);
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $validationException = new StaffValidationException($errors);
            return $validationException->getViolationsArray();
        }

        return [];
    }
}
