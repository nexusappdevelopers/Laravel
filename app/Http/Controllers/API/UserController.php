<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\User\StoreUserRequest;
use App\Http\Requests\API\User\UpdateUserRequest;
use App\Http\Resources\API\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * The user service instance.
     *
     * @var UserService
     */
    protected UserService $userService;

    /**
     * Create a new controller instance.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['store']);
        $this->middleware('permission:users.edit')->only(['update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $filters = $request->only([
                'is_active', 'role', 'created_after', 'created_before',
                'last_login_after', 'last_login_before', 'gender',
                'has_email_verified', 'email_not_verified'
            ]);

            $query = $this->userService->getRepository()->newQuery()
                ->with(['roles', 'permissions']);

            if ($search) {
                $query->search($search);
            }

            if (!empty($filters)) {
                $query->filter($filters);
            }

            $users = $query->paginate($perPage);

            return $this->successResponse([
                'users' => UserResource::collection($users),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ], 'Users retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve users', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());

            return $this->successResponse([
                'user' => new UserResource($user->load(['roles', 'permissions'])),
            ], 'User created successfully', 201);

        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to create user', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->userService->findOrFail($id, ['*'], ['roles', 'permissions']);

            return $this->successResponse([
                'user' => new UserResource($user),
            ], 'User retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve user', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            $user = $this->userService->update($id, $request->validated());

            return $this->successResponse([
                'user' => new UserResource($user->load(['roles', 'permissions'])),
            ], 'User updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update user', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->userService->delete($id);

            return $this->successResponse([], 'User deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to delete user', 500);
        }
    }

    /**
     * Toggle user active status.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function toggleActive(string $id): JsonResponse
    {
        try {
            $user = $this->userService->toggleActive($id);

            return $this->successResponse([
                'user' => new UserResource($user),
            ], 'User status toggled successfully');

        } catch (\Exception $e) {
            Log::error('Failed to toggle user status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to toggle user status', 500);
        }
    }

    /**
     * Get user statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->userService->getStatistics();

            return $this->successResponse([
                'statistics' => $statistics,
            ], 'User statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve user statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve user statistics', 500);
        }
    }

    /**
     * Export users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'is_active', 'role', 'created_after', 'created_before',
                'last_login_after', 'last_login_before', 'gender'
            ]);

            $users = $this->userService->getExportData($filters);

            return $this->successResponse([
                'users' => UserResource::collection($users),
                'exported_at' => now()->toISOString(),
            ], 'Users exported successfully');

        } catch (\Exception $e) {
            Log::error('Failed to export users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to export users', 500);
        }
    }

    /**
     * Bulk delete users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['required', 'string', 'exists:users,id'],
            ]);

            $count = $this->userService->bulkDelete($request->ids);

            return $this->successResponse([
                'deleted_count' => $count,
            ], 'Users deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to bulk delete users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to bulk delete users', 500);
        }
    }
}
