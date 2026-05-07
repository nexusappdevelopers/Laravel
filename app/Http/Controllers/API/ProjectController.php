<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Project\StoreProjectRequest;
use App\Http\Requests\API\Project\UpdateProjectRequest;
use App\Http\Resources\API\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    /**
     * The project service instance.
     *
     * @var ProjectService
     */
    protected ProjectService $projectService;

    /**
     * Create a new controller instance.
     *
     * @param ProjectService $projectService
     */
    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
        $this->middleware('permission:projects.view')->only(['index', 'show']);
        $this->middleware('permission:projects.create')->only(['store']);
        $this->middleware('permission:projects.edit')->only(['update', 'changeStatus', 'updateProgress', 'assignManager']);
        $this->middleware('permission:projects.delete')->only(['destroy']);
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
                'status', 'priority', 'company_id', 'client_id', 'project_manager_id',
                'start_date_from', 'start_date_to', 'end_date_from', 'end_date_to',
                'budget_min', 'budget_max', 'overdue', 'involving_user'
            ]);

            $query = $this->projectService->getRepository()->newQuery()
                ->with(['company', 'client', 'projectManager', 'creator', 'teamMembers'])
                ->withCount(['tasks', 'completedTasks', 'pendingTasks', 'files']);

            if ($search) {
                $query->search($search);
            }

            if (!empty($filters)) {
                $query->filter($filters);
            }

            $projects = $query->paginate($perPage);

            return $this->successResponse([
                'projects' => ProjectResource::collection($projects),
                'pagination' => [
                    'total' => $projects->total(),
                    'per_page' => $projects->perPage(),
                    'current_page' => $projects->currentPage(),
                    'last_page' => $projects->lastPage(),
                    'from' => $projects->firstItem(),
                    'to' => $projects->lastItem(),
                ]
            ], 'Projects retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve projects', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve projects', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProjectRequest $request
     * @return JsonResponse
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        try {
            $project = $this->projectService->create($request->validated());

            return $this->successResponse([
                'project' => new ProjectResource($project->load([
                    'company', 'client', 'projectManager', 'creator', 'teamMembers'
                ])),
            ], 'Project created successfully', 201);

        } catch (\Exception $e) {
            Log::error('Failed to create project', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to create project', 500);
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
            $project = $this->projectService->findOrFail($id, ['*'], [
                'company', 'client', 'projectManager', 'creator', 'teamMembers', 'tasks', 'files'
            ]);

            return $this->successResponse([
                'project' => new ProjectResource($project),
            ], 'Project retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve project', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve project', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProjectRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateProjectRequest $request, string $id): JsonResponse
    {
        try {
            $project = $this->projectService->update($id, $request->validated());

            return $this->successResponse([
                'project' => new ProjectResource($project->load([
                    'company', 'client', 'projectManager', 'creator', 'teamMembers'
                ])),
            ], 'Project updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update project', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update project', 500);
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
            $this->projectService->delete($id);

            return $this->successResponse([], 'Project deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete project', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to delete project', 500);
        }
    }

    /**
     * Change project status.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function changeStatus(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => ['required', 'in:planning,in_progress,on_hold,completed,cancelled'],
            ]);

            $project = $this->projectService->changeStatus($id, $request->status);

            return $this->successResponse([
                'project' => new ProjectResource($project),
            ], 'Project status changed successfully');

        } catch (\Exception $e) {
            Log::error('Failed to change project status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to change project status', 500);
        }
    }

    /**
     * Update project progress.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateProgress(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'progress' => ['required', 'integer', 'min:0', 'max:100'],
            ]);

            $project = $this->projectService->updateProgress($id, $request->progress);

            return $this->successResponse([
                'project' => new ProjectResource($project),
            ], 'Project progress updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update project progress', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to update project progress', 500);
        }
    }

    /**
     * Assign project manager.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function assignManager(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'manager_id' => ['required', 'uuid', 'exists:users,id'],
            ]);

            $project = $this->projectService->assignManager($id, $request->manager_id);

            return $this->successResponse([
                'project' => new ProjectResource($project),
            ], 'Project manager assigned successfully');

        } catch (\Exception $e) {
            Log::error('Failed to assign project manager', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to assign project manager', 500);
        }
    }

    /**
     * Add team member to project.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function addTeamMember(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => ['required', 'uuid', 'exists:users,id'],
            ]);

            $project = $this->projectService->addTeamMember($id, $request->user_id);

            return $this->successResponse([
                'project' => new ProjectResource($project->load('teamMembers')),
            ], 'Team member added successfully');

        } catch (\Exception $e) {
            Log::error('Failed to add team member', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to add team member', 500);
        }
    }

    /**
     * Remove team member from project.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function removeTeamMember(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => ['required', 'uuid', 'exists:users,id'],
            ]);

            $project = $this->projectService->removeTeamMember($id, $request->user_id);

            return $this->successResponse([
                'project' => new ProjectResource($project->load('teamMembers')),
            ], 'Team member removed successfully');

        } catch (\Exception $e) {
            Log::error('Failed to remove team member', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to remove team member', 500);
        }
    }

    /**
     * Get project statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->projectService->getStatistics();

            return $this->successResponse([
                'statistics' => $statistics,
            ], 'Project statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve project statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve project statistics', 500);
        }
    }

    /**
     * Export projects.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'priority', 'company_id', 'client_id', 'project_manager_id',
                'start_date_from', 'start_date_to', 'end_date_from', 'end_date_to',
                'budget_min', 'budget_max'
            ]);

            $projects = $this->projectService->getExportData($filters);

            return $this->successResponse([
                'projects' => ProjectResource::collection($projects),
                'exported_at' => now()->toISOString(),
            ], 'Projects exported successfully');

        } catch (\Exception $e) {
            Log::error('Failed to export projects', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to export projects', 500);
        }
    }
}
