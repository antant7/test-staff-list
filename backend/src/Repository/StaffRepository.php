<?php

namespace App\Repository;

use App\Entity\Staff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Staff>
 */
class StaffRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Staff::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Save staff entity to database
     */
    public function save(Staff $staff, bool $flush = false): void
    {
        $this->entityManager->persist($staff);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Remove staff entity from database
     */
    public function remove(Staff $staff, bool $flush = false): void
    {
        $this->entityManager->remove($staff);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Find all staff members
     */
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }

    /**
     * Find staff by position
     */
    public function findByPosition(string $position): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.position = :position')
            ->setParameter('position', $position)
            ->orderBy('s.id', 'ASC')
//            ->addOrderBy('s.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find staff by email
     */
    public function findByEmail(string $email): ?Staff
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search staff by name (first name or last name)
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.firstName LIKE :searchTerm OR s.lastName LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('s.lastName', 'ASC')
            ->addOrderBy('s.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get staff count
     */
    public function getStaffCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find staff with pagination
     */
    public function findWithPagination(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('s')
            ->orderBy('s.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find staff by pid
     */
    public function findByPid(int $pid): ?Staff
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.pid = :pid')
            ->setParameter('pid', $pid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find staff with filters and pagination
     */
    public function findWithFiltersAndPagination(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        $qb = $this->createQueryBuilder('s');

        // Apply filters
        if (!empty($filters['id'])) {
            $qb->andWhere('s.id = :id')
               ->setParameter('id', $filters['id']);
        }

        if (!empty($filters['email'])) {
            $qb->andWhere('s.email LIKE :email')
               ->setParameter('email', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['last_name'])) {
            $qb->andWhere('s.lastName LIKE :last_name')
               ->setParameter('last_name', $filters['last_name'] . '%');
        }

        return $qb->orderBy('s.id', 'ASC')
                  ->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Get staff count with filters
     */
    public function getStaffCountWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('s')
                   ->select('COUNT(s.id)');

        // Apply filters
        if (!empty($filters['id'])) {
            $qb->andWhere('s.id = :id')
               ->setParameter('id', $filters['id']);
        }

        if (!empty($filters['email'])) {
            $qb->andWhere('s.email LIKE :email')
               ->setParameter('email', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['last_name'])) {
            $qb->andWhere('s.lastName LIKE :last_name')
               ->setParameter('last_name', $filters['last_name'] . '%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find subordinates by manager ID
     */
    public function findSubordinates(int $managerId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.pid = :managerId')
            ->setParameter('managerId', $managerId)
            ->orderBy('s.lastName', 'ASC')
            ->addOrderBy('s.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if staff member has subordinates
     */
    public function hasSubordinates(int $staffId): bool
    {
        $count = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.pid = :staffId')
            ->setParameter('staffId', $staffId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
