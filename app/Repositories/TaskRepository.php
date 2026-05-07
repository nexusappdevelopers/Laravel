<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

class TaskRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     *
     * @param Task $model
     */
    public function __construct(Task $model)
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
            case 'project_id':
                $this->query->where('project_id', $value);
                break;
            case 'assigned_to':
                $this->query->assignedTo($value);
                break;
            case 'created_by':
                $this->query->where('created_by', $value);
                break;
            case 'due_date_from':
                $this->query->whereDate('due_date', '>=', $value);
                break;
            case 'due_date_to':
                $this->query->whereDate('due_date', '<=', $value);
                break;
            case 'overdue':
                if ($value) {
                    $this->query->overdue();
                }
                break;
            case 'due_within':
                $this->query->dueWithin($value);
                break;
            case 'estimated_hours_min':
                $this->query->where('estimated_hours', '>=', $value);
                break;
            case 'estimated_hours_max':
                $this->query->where('estimated_hours', '<=', $value);
                break;
            case 'actual_hours_min':
                $this->query->where('actual_hours', '>=', $value);
                break;
            case 'actual_hours_max':
                $this->query->where('actual_hours', '<=', $value);
                break;
            case 'has_tags':
                if (is_array($value)) {
                    $this->query->whereJsonContains('tags', $value);
                }
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
     * Get tasks by status.
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
     * Get tasks by priority.
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
     * Get tasks for a specific project.
     *
     * @param string $projectId
     * @return $this
     */
    public function forProject(string $projectId): self
    {
        $this->query->where('project_id', $projectId);
        return $this;
    }

    /**
     * Get tasks assigned to a specific user.
     *
     * @param string $userId
     * @return $this
     */
    public function assignedTo(string $userId): self
    {
        $this->query->assignedTo($userId);
        return $this;
    }

    /**
     * Get tasks created by a specific user.
     *
     * @param string $userId
     * @return $this
     */
    public function createdBy(string $userId): self
    {
        $this->query->where('created_by', $userId);
        return $this;
    }

    /**
     * Get overdue tasks.
     *
     * @return $this
     */
    public function overdue(): self
    {
        $this->query->overdue();
        return $this;
    }

    /**
     * Get tasks due within a certain number of days.
     *
     * @param int $days
     * @return $this
     */
    public function dueWithin(int $days): self
    {
        $this->query->dueWithin($days);
        return $this;
    }

    /**
     * Get completed tasks.
     *
     * @return $this
     */
    public function completed(): self
    {
        $this->query->byStatus('completed');
        return $this;
    }

    /**
     * Get pending tasks.
     *
     * @return $this
     */
    public function pending(): self
    {
        $this->query->where('status', '!=', 'completed');
        return $this;
    }

    /**
     * Get tasks in progress.
     *
     * @return $this
     */
    public function inProgress(): self
    {
        $this->query->byStatus('in_progress');
        return $this;
    }

    /**
     * Get tasks in review.
     *
     * @return $this
     */
    public function inReview(): self
    {
        $this->query->byStatus('review');
        return $this;
    }

    /**
     * Get tasks to do.
     *
     * @return $this
     */
    public function todo(): self
    {
        $this->query->byStatus('todo');
        return $this;
    }

    /**
     * Get tasks due today.
     *
     * @return $this
     */
    public function dueToday(): self
    {
        $this->query->whereDate('due_date', today())
                   ->where('status', '!=', 'completed');
        return $this;
    }

    /**
     * Get tasks due this week.
     *
     * @return $this
     */
    public function dueThisWeek(): self
    {
        $this->query->whereDate('due_date', '>=', now()->startOfWeek())
                   ->whereDate('due_date', '<=', now()->endOfWeek())
                   ->where('status', '!=', 'completed');
        return $this;
    }

    /**
     * Get tasks due this month.
     *
     * @return $this
     */
    public function dueThisMonth(): self
    {
        $this->query->whereMonth('due_date', now()->month)
                   ->whereYear('due_date', now()->year)
                   ->where('status', '!=', 'completed');
        return $this;
    }

    /**
     * Get tasks with estimated hours within a range.
     *
     * @param int $minHours
     * @param int $maxHours
     * @return $this
     */
    public function estimatedHoursBetween(int $minHours, int $maxHours): self
    {
        $this->query->where('estimated_hours', '>=', $minHours)
                   ->where('estimated_hours', '<=', $maxHours);
        return $this;
    }

    /**
     * Get tasks with actual hours within a range.
     *
     * @param int $minHours
     * @param int $maxHours
     * @return $this
     */
    public function actualHoursBetween(int $minHours, int $maxHours): self
    {
        $this->query->where('actual_hours', '>=', $minHours)
                   ->where('actual_hours', '<=', $maxHours);
        return $this;
    }

    /**
     * Get tasks with specific tags.
     *
     * @param array $tags
     * @return $this
     */
    public function withTags(array $tags): self
    {
        $this->query->whereJsonContains('tags', $tags);
        return $this;
    }

    /**
     * Get tasks without assigned user.
     *
     * @return $this
     */
    public function unassigned(): self
    {
        $this->query->whereNull('assigned_to');
        return $this;
    }

    /**
     * Get tasks with assigned user.
     *
     * @return $this
     */
    public function assigned(): self
    {
        $this->query->whereNotNull('assigned_to');
        return $this;
    }

    /**
     * Get tasks without due date.
     *
     * @return $this
     */
    public function withoutDueDate(): self
    {
        $this->query->whereNull('due_date');
        return $this;
    }

    /**
     * Get tasks with due date.
     *
     * @return $this
     */
    public function withDueDate(): self
    {
        $this->query->whereNotNull('due_date');
        return $this;
    }

    /**
     * Get tasks with files.
     *
     * @return $this
     */
    public function withFiles(): self
    {
        $this->query->whereHas('files');
        return $this;
    }

    /**
     * Get tasks with activity logs.
     *
     * @return $this
     */
    public function withActivity(): self
    {
        $this->query->whereHas('activities');
        return $this;
    }

    /**
     * Get tasks sorted by priority.
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
     * Get tasks sorted by due date.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByDueDate(string $direction = 'asc'): self
    {
        $this->query->orderBy('due_date', $direction);
        return $this;
    }

    /**
     * Get tasks sorted by created date.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByCreatedAt(string $direction = 'desc'): self
    {
        $this->query->orderBy('created_at', $direction);
        return $this;
    }

    /**
     * Get tasks sorted by completed date.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByCompletedAt(string $direction = 'desc'): self
    {
        $this->query->orderBy('completed_at', $direction);
        return $this;
    }

    /**
     * Get tasks sorted by estimated hours.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByEstimatedHours(string $direction = 'desc'): self
    {
        $this->query->orderBy('estimated_hours', $direction);
        return $this;
    }

    /**
     * Get tasks sorted by actual hours.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByActualHours(string $direction = 'desc'): self
    {
        $this->query->orderBy('actual_hours', $direction);
        return $this;
    }

    /**
     * Get tasks with relationships.
     *
     * @return $this
     */
    public function withRelationships(): self
    {
        $this->query->with([
            'project',
            'assignee',
            'creator',
            'files',
            'activities'
        ]);
        return $this;
    }

    /**
     * Get tasks with file counts.
     *
     * @return $this
     */
    public function withFileCounts(): self
    {
        $this->query->withCount('files');
        return $this;
    }

    /**
     * Get tasks with activity counts.
     *
     * @return $this
     */
    public function withActivityCounts(): self
    {
        $this->query->withCount('activities');
        return $this;
    }

    /**
     * Get tasks created within the last X days.
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
     * Get tasks completed within the last X days.
     *
     * @param int $days
     * @return $this
     */
    public function completedLastDays(int $days): self
    {
        $this->query->whereDate('completed_at', '>=', now()->subDays($days));
        return $this;
    }

    /**
     * Get tasks with high priority.
     *
     * @return $this
     */
    public function highPriority(): self
    {
        $this->query->whereIn('priority', ['high', 'urgent']);
        return $this;
    }

    /**
     * Get tasks with medium priority.
     *
     * @return $this
     */
    public function mediumPriority(): self
    {
        $this->query->where('priority', 'medium');
        return $this;
    }

    /**
     * Get tasks with low priority.
     *
     * @return $this
     */
    public function lowPriority(): self
    {
        $this->query->where('priority', 'low');
        return $this;
    }

    /**
     * Get tasks with urgent priority.
     *
     * @return $this
     */
    public function urgentPriority(): self
    {
        $this->query->where('priority', 'urgent');
        return $this;
    }

    /**
     * Get tasks for multiple projects.
     *
     * @param array $projectIds
     * @return $this
     */
    public function forProjects(array $projectIds): self
    {
        $this->query->whereIn('project_id', $projectIds);
        return $this;
    }

    /**
     * Get tasks assigned to multiple users.
     *
     * @param array $userIds
     * @return $this
     */
    public function assignedToUsers(array $userIds): self
    {
        $this->query->whereIn('assigned_to', $userIds);
        return $this;
    }

    /**
     * Get tasks created by multiple users.
     *
     * @param array $userIds
     * @return $this
     */
    public function createdByUsers(array $userIds): self
    {
        $this->query->whereIn('created_by', $userIds);
        return $this;
    }

    /**
     * Get tasks with multiple statuses.
     *
     * @param array $statuses
     * @return $this
     */
    public function withStatuses(array $statuses): self
    {
        $this->query->whereIn('status', $statuses);
        return $this;
    }

    /**
     * Get tasks with multiple priorities.
     *
     * @param array $priorities
     * @return $this
     */
    public function withPriorities(array $priorities): self
    {
        $this->query->whereIn('priority', $priorities);
        return $this;
    }
}
