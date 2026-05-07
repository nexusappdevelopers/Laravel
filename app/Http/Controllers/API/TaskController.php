<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Task\StoreTaskRequest;
use App\Http\Requests\API\Task\UpdateTaskRequest;
use App\Http\Resources\API\TaskResource;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * The task service instance.
     *
     * @var TaskService
     */
    protected TaskService $taskService;

    /**
     * Create a new controller instance.
     *
     * @param TaskService $taskService
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
        $this->middleware('permission:tasks.view')->only(['index', 'show']);
        $this->middleware('permission:tasks.create')->only(['store']);
        $this->middleware('permission:tasks.edit')->only(['update', 'changeStatus']);
        $this->middleware('permission:tasks.delete')->only(['destroy']);
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
                'status', 'priority', 'project_id', 'assigned_to', 'created_by',
                'due_date_from', 'due_date_to', 'overdue', 'due_within',
                'estimated_hours_min', 'estimated_hours_max', 'actual_hours_min',
                'actual_hours_max', 'has_tags'
            ]);

            $query = $this->taskService->getRepository()->newQuery()
                ->with(['project', 'assignee', 'creator', 'files'])
                ->withCount(['files']);

            if ($search) {
                $query->search($search);
            }

            if (!empty($filters)) {
                $query->filter($filters);
            }

            $tasks = $query->paginate($perPage);

            return $this->successResponse([
                'tasks' => TaskResource::collection($tasks),
                'pagination' => [
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem(),
                ]
            ], 'Tasks retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve tasks', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTaskRequest $request
     * @return JsonResponse
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        try {
            $task = $this->taskService->create($request->validated());

            return $this->successResponse([
                'task' => new TaskResource($task->load([
                    'project', 'assignee', 'creator', 'files'
                ])),
            ], 'Task created successfully', 201);

        } catch (\Exception $e) {
            Log::error('Failed to create task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to create task', 500);
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
            $task = $this->taskService->findOrFail($id, ['*'], [
                'project', 'assignee', 'creator', 'files'
            ]);

            return $this->successResponse([
                'task' => new TaskResource($task),
            ], 'Task retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve task', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTaskRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateTaskRequest $request, string $id): JsonResponse
    {
        try {
            $task = $this->taskService->update($id, $request->validated());

            return $this->successResponse([
                'task' => new TaskResource($task->load([
                    'project', 'assignee', 'creator', 'files'
                ])),
            ], 'Task updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update task', 500);
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
            $this->taskService->delete($id);

            return $this->successResponse([], 'Task deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to delete task', 500);
        }
    }

    /**
     * Change task status.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => ['required', 'in:todo,in_progress,review,completed,cancelled'],
            ]);

            $task = $this->taskService->changeStatus($id, $request->status);

            return $this->successResponse([
                'task' => new TaskResource($task),
            ], 'Task status changed successfully');

        } catch (\Exception $e) {
            Log::error('Failed to change task status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to change task status', 500);
        }
    }

    /**
     * Assign task to user.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function assignTask(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => ['required', 'uuid', 'exists:users,id'],
            ]);

            $task = $this->taskService->assignTask($id, $request->user_id);

            return $this->successResponse([
                'task' => new TaskResource($task->load('assignee')),
            ], 'Task assigned successfully');

        } catch (\Exception $e) {
            Log::error('Failed to assign task', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to assign task', 500);
        }
    }

    /**
     * Get task statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->taskService->getStatistics();

            return $this->successResponse([
                'statistics' => $statistics,
            ], 'Task statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve task statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve task statistics', 500);
        }
    }

    /**
     * Export tasks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'priority', 'project_id', 'assigned_to', 'created_by',
                'due_date_from', 'due_date_to', 'overdue', 'due_within',
                'estimated_hours_min', 'estimated_hours_max', 'actual_hours_min',
                'actual_hours_max'
            ]);

            $tasks = $this->taskService->getExportData($filters);

            return $this->successResponse([
                'tasks' => TaskResource::collection($tasks),
                'exported_at' => now()->toISOString(),
            ], 'Tasks exported successfully');

        } catch (\Exception $e) {
            Log::error('Failed to export tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to export tasks', 500);
        }
    }

    /**
     * Get my tasks (for authenticated user).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myTasks(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $filters = $request->only([
                'status', 'priority', 'due_date_from', 'due_date_to', 'overdue', 'due_within'
            ]);

            $query = $this->taskService->getRepository()->newQuery()
                ->where('assigned_to', $user->id)
                ->with(['project', 'creator', 'files'])
                ->withCount(['files']);

            if (!empty($filters)) {
                $query->filter($filters);
            }

            $tasks = $query->paginate($perPage);

            return $this->successResponse([
                'tasks' => TaskResource::collection($tasks),
                'pagination' => [
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem(),
                ]
            ], 'My tasks retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve my tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve my tasks', 500);
        }
    }
}
