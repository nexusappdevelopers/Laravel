<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Services\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProjectService extends BaseService implements BaseServiceInterface
{
    /**
     * The validation rules for creation.
     *
     * @var array
     */
    protected array $createRules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'requirements' => 'nullable|string',
        'status' => 'required|in:planning,in_progress,on_hold,completed,cancelled',
        'priority' => 'required|in:low,medium,high,urgent',
        'budget' => 'nullable|numeric|min:0',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'company_id' => 'nullable|uuid|exists:companies,id',
        'client_id' => 'nullable|uuid|exists:users,id',
        'project_manager_id' => 'nullable|uuid|exists:users,id',
        'team_members' => 'nullable|array',
        'team_members.*' => 'uuid|exists:users,id',
        'repository_url' => 'nullable|url',
        'demo_url' => 'nullable|url',
        'production_url' => 'nullable|url',
        'notes' => 'nullable|string',
        'progress_percentage' => 'integer|min:0|max:100',
    ];

    /**
     * The validation rules for update.
     *
     * @var array
     */
    protected array $updateRules = [
        'name' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'requirements' => 'nullable|string',
        'status' => 'sometimes|required|in:planning,in_progress,on_hold,completed,cancelled',
        'priority' => 'sometimes|required|in:low,medium,high,urgent',
        'budget' => 'nullable|numeric|min:0',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'company_id' => 'nullable|uuid|exists:companies,id',
        'client_id' => 'nullable|uuid|exists:users,id',
        'project_manager_id' => 'nullable|uuid|exists:users,id',
        'team_members' => 'nullable|array',
        'team_members.*' => 'uuid|exists:users,id',
        'repository_url' => 'nullable|url',
        'demo_url' => 'nullable|url',
        'production_url' => 'nullable|url',
        'notes' => 'nullable|string',
        'progress_percentage' => 'integer|min:0|max:100',
    ];

    /**
     * Create a new service instance.
     *
     * @param ProjectRepository $repository
     */
    public function __construct(ProjectRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new project.
     *
     * @param array $data
     * @return Project
     */
    public function create(array $data): Project
    {
        return $this->transaction(function () use ($data) {
            $validatedData = $this->validateCreate($data);
            $transformedData = $this->transformData($validatedData);
            
            $project = $this->repository->create($transformedData);
            
            // Sync team members if provided
            if (isset($data['team_members'])) {
                $project->teamMembers()->sync($data['team_members']);
            }
            
            // Log activity
            $this->logActivity('project_created', $project, [
                'name' => $project->name,
                'status' => $project->status,
                'priority' => $project->priority,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\ProjectCreated($project));
            
            return $project;
        });
    }

    /**
     * Update a project.
     *
     * @param string|int $id
     * @param array $data
     * @return Project
     */
    public function update($id, array $data): Project
    {
        return $this->transaction(function () use ($id, $data) {
            $project = $this->repository->findOrFail($id);
            $validatedData = $this->validateUpdate($id, $data);
            $transformedData = $this->transformData($validatedData);
            
            $oldData = $project->toArray();
            $project->update($transformedData);
            
            // Sync team members if provided
            if (isset($data['team_members'])) {
                $project->teamMembers()->sync($data['team_members']);
            }
            
            // Log activity
            $this->logActivity('project_updated', $project, [
                'old_data' => $oldData,
                'new_data' => $transformedData,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\ProjectUpdated($project, $oldData));
            
            return $project;
        });
    }

    /**
     * Delete a project.
     *
     * @param string|int $id
     * @return bool
     */
    public function delete($id): bool
    {
        return $this->transaction(function () use ($id) {
            $project = $this->repository->findOrFail($id);
            
            // Log activity
            $this->logActivity('project_deleted', $project, [
                'name' => $project->name,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\ProjectDeleted($project));
            
            return $this->repository->delete($id);
        });
    }

    /**
     * Update project progress.
     *
     * @param string|int $id
     * @param int $progress
     * @return Project
     */
    public function updateProgress($id, int $progress): Project
    {
        return $this->transaction(function () use ($id, $progress) {
            $project = $this->repository->findOrFail($id);
            
            $project->update(['progress_percentage' => $progress]);
            
            // Auto-complete project if progress reaches 100%
            if ($progress >= 100 && $project->status !== 'completed') {
                $project->update(['status' => 'completed']);
                $this->fireEvent(new \App\Events\ProjectCompleted($project));
            }
            
            // Log activity
            $this->logActivity('project_progress_updated', $project, [
                'progress' => $progress,
            ]);
            
            return $project;
        });
    }

    /**
     * Change project status.
     *
     * @param string|int $id
     * @param string $status
     * @return Project
     */
    public function changeStatus($id, string $status): Project
    {
        return $this->transaction(function () use ($id, $status) {
            $project = $this->repository->findOrFail($id);
            
            $oldStatus = $project->status;
            $project->update(['status' => $status]);
            
            // Auto-set progress to 100% if status is completed
            if ($status === 'completed') {
                $project->update(['progress_percentage' => 100]);
                $this->fireEvent(new \App\Events\ProjectCompleted($project));
            }
            
            // Log activity
            $this->logActivity('project_status_changed', $project, [
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\ProjectStatusChanged($project, $oldStatus, $status));
            
            return $project;
        });
    }

    /**
     * Assign project manager.
     *
     * @param string|int $id
     * @param string $managerId
     * @return Project
     */
    public function assignManager($id, string $managerId): Project
    {
        return $this->transaction(function () use ($id, $managerId) {
            $project = $this->repository->findOrFail($id);
            
            $oldManagerId = $project->project_manager_id;
            $project->update(['project_manager_id' => $managerId]);
            
            // Log activity
            $this->logActivity('project_manager_assigned', $project, [
                'old_manager_id' => $oldManagerId,
                'new_manager_id' => $managerId,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\ProjectManagerAssigned($project, $oldManagerId, $managerId));
            
            return $project;
        });
    }

    /**
     * Add team member to project.
     *
     * @param string|int $id
     * @param string $userId
     * @return Project
     */
    public function addTeamMember($id, string $userId): Project
    {
        return $this->transaction(function () use ($id, $userId) {
            $project = $this->repository->findOrFail($id);
            
            $project->teamMembers()->syncWithoutDetaching([$userId]);
            
            // Log activity
            $this->logActivity('team_member_added', $project, [
                'user_id' => $userId,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\TeamMemberAdded($project, $userId));
            
            return $project;
        });
    }

    /**
     * Remove team member from project.
     *
     * @param string|int $id
     * @param string $userId
     * @return Project
     */
    public function removeTeamMember($id, string $userId): Project
    {
        return $this->transaction(function () use ($id, $userId) {
            $project = $this->repository->findOrFail($id);
            
            $project->teamMembers()->detach($userId);
            
            // Log activity
            $this->logActivity('team_member_removed', $project, [
                'user_id' => $userId,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\TeamMemberRemoved($project, $userId));
            
            return $project;
        });
    }

    /**
     * Get projects by status.
     *
     * @param string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->byStatus($status)->paginate($perPage);
    }

    /**
     * Get projects by priority.
     *
     * @param string $priority
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByPriority(string $priority, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->byPriority($priority)->paginate($perPage);
    }

    /**
     * Get active projects.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveProjects(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->active()->paginate($perPage);
    }

    /**
     * Get completed projects.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getCompletedProjects(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->completed()->paginate($perPage);
    }

    /**
     * Get overdue projects.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getOverdueProjects(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->overdue()->paginate($perPage);
    }

    /**
     * Get projects for a specific company.
     *
     * @param string $companyId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getForCompany(string $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->forCompany($companyId)->paginate($perPage);
    }

    /**
     * Get projects for a specific client.
     *
     * @param string $clientId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getForClient(string $clientId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->forClient($clientId)->paginate($perPage);
    }

    /**
     * Get projects managed by a specific user.
     *
     * @param string $managerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getManagedBy(string $managerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->managedBy($managerId)->paginate($perPage);
    }

    /**
     * Get projects involving a specific user.
     *
     * @param string $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInvolvingUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->involvingUser($userId)->paginate($perPage);
    }

    /**
     * Get projects with pending tasks.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWithPendingTasks(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->withPendingTasks()->paginate($perPage);
    }

    /**
     * Get projects ending in the next X days.
     *
     * @param int $days
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getEndingInDays(int $days, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->endingInDays($days)->paginate($perPage);
    }

    /**
     * Get statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $baseStats = parent::getStatistics();
        
        return array_merge($baseStats, [
            'by_status' => [
                'planning' => $this->repository->byStatus('planning')->count(),
                'in_progress' => $this->repository->byStatus('in_progress')->count(),
                'on_hold' => $this->repository->byStatus('on_hold')->count(),
                'completed' => $this->repository->byStatus('completed')->count(),
                'cancelled' => $this->repository->byStatus('cancelled')->count(),
            ],
            'by_priority' => [
                'low' => $this->repository->byPriority('low')->count(),
                'medium' => $this->repository->byPriority('medium')->count(),
                'high' => $this->repository->byPriority('high')->count(),
                'urgent' => $this->repository->byPriority('urgent')->count(),
            ],
            'overdue' => $this->repository->overdue()->count(),
            'active' => $this->repository->active()->count(),
            'ending_this_week' => $this->repository->endingInDays(7)->count(),
            'ending_this_month' => $this->repository->endingInDays(30)->count(),
            'with_pending_tasks' => $this->repository->withPendingTasks()->count(),
            'with_overdue_tasks' => $this->repository->withOverdueTasks()->count(),
            'average_progress' => $this->repository->query()->avg('progress_percentage'),
            'total_budget' => $this->repository->query()->sum('budget'),
        ]);
    }

    /**
     * Transform data for storage.
     *
     * @param array $data
     * @return array
     */
    protected function transformData(array $data): array
    {
        // Add created_by if not present
        if (!isset($data['created_by']) && auth()->check()) {
            $data['created_by'] = auth()->id();
        }
        
        return $data;
    }

    /**
     * Transform model for response.
     *
     * @param Project $model
     * @return array
     */
    public function transformModel(Project $model): array
    {
        return array_merge($model->toArray(), [
            'duration' => $model->duration,
            'status_color' => $model->status_color,
            'priority_color' => $model->priority_color,
            'is_overdue' => $model->isOverdue(),
            'team_members_count' => $model->teamMembers()->count(),
            'tasks_count' => $model->tasks()->count(),
            'completed_tasks_count' => $model->completedTasks()->count(),
            'pending_tasks_count' => $model->pendingTasks()->count(),
            'files_count' => $model->files()->count(),
        ]);
    }

    /**
     * Get the repository instance.
     *
     * @return ProjectRepository
     */
    protected function getRepository(): ProjectRepository
    {
        return $this->repository;
    }
}
