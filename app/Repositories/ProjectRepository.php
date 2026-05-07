<?php

namespace App\Repositories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

class ProjectRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     *
     * @param Project $model
     */
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    /**
     * Apply a filter to the query.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function applyFilter(string $key, $value): void
    {
        switch ($key) {
            case 'status':
                $this->query->byStatus($value);
                break;
            case 'priority':
                $this->query->byPriority($value);
                break;
            case 'company_id':
                $this->query->where('company_id', $value);
                break;
            case 'client_id':
                $this->query->where('client_id', $value);
                break;
            case 'project_manager_id':
                $this->query->where('project_manager_id', $value);
                break;
            case 'start_date_from':
                $this->query->whereDate('start_date', '>=', $value);
                break;
            case 'start_date_to':
                $this->query->whereDate('start_date', '<=', $value);
                break;
            case 'end_date_from':
                $this->query->whereDate('end_date', '>=', $value);
                break;
            case 'end_date_to':
                $this->query->whereDate('end_date', '<=', $value);
                break;
            case 'budget_min':
                $this->query->where('budget', '>=', $value);
                break;
            case 'budget_max':
                $this->query->where('budget', '<=', $value);
                break;
            case 'overdue':
                if ($value) {
                    $this->query->whereDate('end_date', '<', now())
                                ->where('status', '!=', 'completed');
                }
                break;
            case 'involving_user':
                $this->query->involvingUser($value);
                break;
        }
    }

    /**
     * Apply search to the query.
     *
     * @param string $search
     * @return void
     */
    protected function applySearch(string $search): void
    {
        $this->query->search($search);
    }

    /**
     * Get projects by status.
     *
     * @param string $status
     * @return $this
     */
    public function byStatus(string $status): self
    {
        $this->query->byStatus($status);
        return $this;
    }

    /**
     * Get projects by priority.
     *
     * @param string $priority
     * @return $this
     */
    public function byPriority(string $priority): self
    {
        $this->query->byPriority($priority);
        return $this;
    }

    /**
     * Get projects for a specific company.
     *
     * @param string $companyId
     * @return $this
     */
    public function forCompany(string $companyId): self
    {
        $this->query->where('company_id', $companyId);
        return $this;
    }

    /**
     * Get projects for a specific client.
     *
     * @param string $clientId
     * @return $this
     */
    public function forClient(string $clientId): self
    {
        $this->query->where('client_id', $clientId);
        return $this;
    }

    /**
     * Get projects managed by a specific user.
     *
     * @param string $managerId
     * @return $this
     */
    public function managedBy(string $managerId): self
    {
        $this->query->where('project_manager_id', $managerId);
        return $this;
    }

    /**
     * Get overdue projects.
     *
     * @return $this
     */
    public function overdue(): self
    {
        $this->query->whereDate('end_date', '<', now())
                   ->where('status', '!=', 'completed');
        return $this;
    }

    /**
     * Get projects that are currently active.
     *
     * @return $this
     */
    public function active(): self
    {
        $this->query->whereIn('status', ['planning', 'in_progress']);
        return $this;
    }

    /**
     * Get completed projects.
     *
     * @return $this
     */
    public function completed(): self
    {
        $this->query->byStatus('completed');
        return $this;
    }

    /**
     * Get projects starting within a date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return $this
     */
    public function startingBetween(string $startDate, string $endDate): self
    {
        $this->query->whereDate('start_date', '>=', $startDate)
                   ->whereDate('start_date', '<=', $endDate);
        return $this;
    }

    /**
     * Get projects ending within a date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return $this
     */
    public function endingBetween(string $startDate, string $endDate): self
    {
        $this->query->whereDate('end_date', '>=', $startDate)
                   ->whereDate('end_date', '<=', $endDate);
        return $this;
    }

    /**
     * Get projects within a budget range.
     *
     * @param float $minBudget
     * @param float $maxBudget
     * @return $this
     */
    public function withinBudget(float $minBudget, float $maxBudget): self
    {
        $this->query->where('budget', '>=', $minBudget)
                   ->where('budget', '<=', $maxBudget);
        return $this;
    }

    /**
     * Get projects involving a specific user.
     *
     * @param string $userId
     * @return $this
     */
    public function involvingUser(string $userId): self
    {
        $this->query->involvingUser($userId);
        return $this;
    }

    /**
     * Get projects with pending tasks.
     *
     * @return $this
     */
    public function withPendingTasks(): self
    {
        $this->query->whereHas('tasks', function (Builder $query) {
            $query->where('status', '!=', 'completed');
        });
        return $this;
    }

    /**
     * Get projects with overdue tasks.
     *
     * @return $this
     */
    public function withOverdueTasks(): self
    {
        $this->query->whereHas('tasks', function (Builder $query) {
            $query->overdue();
        });
        return $this;
    }

    /**
     * Get projects with files.
     *
     * @return $this
     */
    public function withFiles(): self
    {
        $this->query->whereHas('files');
        return $this;
    }

    /**
     * Get projects with activity logs.
     *
     * @return $this
     */
    public function withActivity(): self
    {
        $this->query->whereHas('activities');
        return $this;
    }

    /**
     * Get projects sorted by priority.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByPriority(string $direction = 'desc'): self
    {
        $priorityOrder = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        
        if ($direction === 'desc') {
            $this->query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')");
        } else {
            $this->query->orderByRaw("FIELD(priority, 'low', 'medium', 'high', 'urgent')");
        }
        
        return $this;
    }

    /**
     * Get projects sorted by progress.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByProgress(string $direction = 'asc'): self
    {
        $this->query->orderBy('progress_percentage', $direction);
        return $this;
    }

    /**
     * Get projects sorted by end date.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByEndDate(string $direction = 'asc'): self
    {
        $this->query->orderBy('end_date', $direction);
        return $this;
    }

    /**
     * Get projects sorted by budget.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByBudget(string $direction = 'desc'): self
    {
        $this->query->orderBy('budget', $direction);
        return $this;
    }

    /**
     * Get projects with relationships.
     *
     * @return $this
     */
    public function withRelationships(): self
    {
        $this->query->with([
            'company',
            'client',
            'projectManager',
            'creator',
            'tasks',
            'files',
            'activities'
        ]);
        return $this;
    }

    /**
     * Get projects with task counts.
     *
     * @return $this
     */
    public function withTaskCounts(): self
    {
        $this->query->withCount([
            'tasks',
            'completedTasks',
            'pendingTasks'
        ]);
        return $this;
    }

    /**
     * Get projects with file counts.
     *
     * @return $this
     */
    public function withFileCounts(): self
    {
        $this->query->withCount('files');
        return $this;
    }

    /**
     * Get projects with activity counts.
     *
     * @return $this
     */
    public function withActivityCounts(): self
    {
        $this->query->withCount('activities');
        return $this;
    }

    /**
     * Get projects by industry.
     *
     * @param string $industry
     * @return $this
     */
    public function byIndustry(string $industry): self
    {
        $this->query->whereHas('company', function (Builder $query) use ($industry) {
            $query->where('industry', $industry);
        });
        return $this;
    }

    /**
     * Get projects created within the last X days.
     *
     * @param int $days
     * @return $this
     */
    public function createdLastDays(int $days): self
    {
        $this->query->whereDate('created_at', '>=', now()->subDays($days));
        return $this;
    }

    /**
     * Get projects ending in the next X days.
     *
     * @param int $days
     * @return $this
     */
    public function endingInDays(int $days): self
    {
        $this->query->whereDate('end_date', '<=', now()->addDays($days))
                   ->whereDate('end_date', '>=', now())
                   ->where('status', '!=', 'completed');
        return $this;
    }

    /**
     * Get projects with high priority.
     *
     * @return $this
     */
    public function highPriority(): self
    {
        $this->query->whereIn('priority', ['high', 'urgent']);
        return $this;
    }

    /**
     * Get projects with medium priority.
     *
     * @return $this
     */
    public function mediumPriority(): self
    {
        $this->query->where('priority', 'medium');
        return $this;
    }

    /**
     * Get projects with low priority.
     *
     * @return $this
     */
    public function lowPriority(): self
    {
        $this->query->where('priority', 'low');
        return $this;
    }
}
