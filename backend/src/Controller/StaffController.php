<?php

namespace App\Controller;

use App\Exception\StaffHasSubordinatesException;
use App\Exception\StaffNotFoundException;
use App\Exception\StaffValidationException;
use App\Service\StaffService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/staff', name: 'api_staff_')]
class StaffController extends AbstractController
{
    private StaffService $staffService;

    public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }

    /**
     * Get all staff members
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 10);
            $search = $request->query->get('search');
            $position = $request->query->get('position');
            
            // Get filters from query parameters
            $filters = $request->query->all('filters') ?? [];
            
            // Clean up empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            // Handle legacy search parameters
            if ($search) {
                $staff = $this->staffService->searchStaffByName($search);
                $totalCount = count($staff);
                $result = [
                    'data' => array_map([$this->staffService, 'staffToArray'], $staff),
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $totalCount,
                        'total' => $totalCount,
                        'total_pages' => 1,
                        'has_next' => false,
                        'has_prev' => false
                    ]
                ];
            } elseif ($position) {
                $staff = $this->staffService->getStaffByPosition($position);
                $totalCount = count($staff);
                $result = [
                    'data' => array_map([$this->staffService, 'staffToArray'], $staff),
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $totalCount,
                        'total' => $totalCount,
                        'total_pages' => 1,
                        'has_next' => false,
                        'has_prev' => false
                    ]
                ];
            } elseif (!empty($filters)) {
                // Use new filtering system
                $result = $this->staffService->getStaffWithFiltersAndPagination($filters, $page, $limit);
                $result['data'] = array_map([$this->staffService, 'staffToArray'], $result['data']);
            } else {
                // Default pagination without filters
                $result = $this->staffService->getStaffWithPagination($page, $limit);
                $result['data'] = array_map([$this->staffService, 'staffToArray'], $result['data']);
            }

            return $this->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination'] ?? null
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching staff: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get staff member by ID
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $staff = $this->staffService->getStaffById($id);

            if (!$staff) {
                return $this->json([
                    'success' => false,
                    'message' => 'Staff member not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'success' => true,
                'data' => $this->staffService->staffToArray($staff)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching staff member: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create new staff member
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check if email is already taken
            if (isset($data['email']) && $this->staffService->isEmailTaken($data['email'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Email is already taken'
                ], Response::HTTP_CONFLICT);
            }

            $staff = $this->staffService->createStaff($data);

            return $this->json([
                'success' => true,
                'message' => 'Staff member created successfully',
                'data' => $this->staffService->staffToArray($staff)
            ], Response::HTTP_CREATED);
        } catch (StaffValidationException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getViolationsArray()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error creating staff member: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update staff member
     */
    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check if email is already taken by another staff member
            if (isset($data['email']) && $this->staffService->isEmailTaken($data['email'], $id)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Email is already taken'
                ], Response::HTTP_CONFLICT);
            }

            $staff = $this->staffService->updateStaff($id, $data);

            return $this->json([
                'success' => true,
                'message' => 'Staff member updated successfully',
                'data' => $this->staffService->staffToArray($staff)
            ]);
        } catch (StaffValidationException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getViolationsArray()
            ], Response::HTTP_BAD_REQUEST);
        } catch (StaffNotFoundException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating staff member: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete staff member
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->staffService->deleteStaff($id);

            return $this->json([
                'success' => true,
                'message' => 'Staff member deleted successfully'
            ]);
        } catch (StaffHasSubordinatesException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (StaffNotFoundException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting staff member: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
