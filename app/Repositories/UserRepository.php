<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     *
     * @param User $model
     */
    public function __construct(User $model)
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
            case 'is_active':
                $this->query->where('is_active', $value);
                break;
            case 'role':
                $this->query->whereHas('roles', function (Builder $query) use ($value) {
                    $query->where('name', $value);
                });
                break;
            case 'created_after':
                $this->query->whereDate('created_at', '>=', $value);
                break;
            case 'created_before':
                $this->query->whereDate('created_at', '<=', $value);
                break;
            case 'last_login_after':
                $this->query->whereDate('last_login_at', '>=', $value);
                break;
            case 'last_login_before':
                $this->query->whereDate('last_login_at', '<=', $value);
                break;
            case 'gender':
                $this->query->where('gender', $value);
                break;
            case 'has_email_verified':
                $this->query->whereNotNull('email_verified_at');
                break;
            case 'email_not_verified':
                $this->query->whereNull('email_verified_at');
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
     * Get active users.
     *
     * @return $this
     */
    public function active(): self
    {
        $this->query->active();
        return $this;
    }

    /**
     * Get users by role.
     *
     * @param string $roleName
     * @return $this
     */
    public function byRole(string $roleName): self
    {
        $this->query->whereHas('roles', function (Builder $query) use ($roleName) {
            $query->where('name', $roleName);
        });
        return $this;
    }

    /**
     * Get users with email verified.
     *
     * @return $this
     */
    public function emailVerified(): self
    {
        $this->query->whereNotNull('email_verified_at');
        return $this;
    }

    /**
     * Get users without email verified.
     *
     * @return $this
     */
    public function emailNotVerified(): self
    {
        $this->query->whereNull('email_verified_at');
        return $this;
    }

    /**
     * Get users who logged in within the last X days.
     *
     * @param int $days
     * @return $this
     */
    public function loggedInLastDays(int $days): self
    {
        $this->query->whereDate('last_login_at', '>=', now()->subDays($days));
        return $this;
    }

    /**
     * Get users created within the last X days.
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
     * Get users by gender.
     *
     * @param string $gender
     * @return $this
     */
    public function byGender(string $gender): self
    {
        $this->query->where('gender', $gender);
        return $this;
    }

    /**
     * Get users with incomplete profiles.
     *
     * @param int $threshold
     * @return $this
     */
    public function withIncompleteProfiles(int $threshold = 80): self
    {
        // This is a simplified approach - in a real application,
        // you might want to calculate this more precisely
        $this->query->where(function ($query) {
            $query->whereNull('phone')
                  ->orWhereNull('bio')
                  ->orWhereNull('date_of_birth');
        });
        return $this;
    }

    /**
     * Get users with complete profiles.
     *
     * @return $this
     */
    public function withCompleteProfiles(): self
    {
        $this->query->whereNotNull('phone')
                   ->whereNotNull('bio')
                   ->whereNotNull('date_of_birth');
        return $this;
    }

    /**
     * Get users who are project managers.
     *
     * @return $this
     */
    public function projectManagers(): self
    {
        $this->query->whereHas('managedProjects');
        return $this;
    }

    /**
     * Get users who are clients.
     *
     * @return $this
     */
    public function clients(): self
    {
        $this->query->whereHas('clientProjects');
        return $this;
    }

    /**
     * Get users who have assigned tasks.
     *
     * @return $this
     */
    public function withAssignedTasks(): self
    {
        $this->query->whereHas('assignedTasks');
        return $this;
    }

    /**
     * Get users by age range.
     *
     * @param int $minAge
     * @param int $maxAge
     * @return $this
     */
    public function byAgeRange(int $minAge, int $maxAge): self
    {
        $minDate = now()->subYears($maxAge);
        $maxDate = now()->subYears($minAge);
        
        $this->query->whereDate('date_of_birth', '>=', $minDate)
                   ->whereDate('date_of_birth', '<=', $maxDate);
        return $this;
    }

    /**
     * Get users with pending tasks.
     *
     * @return $this
     */
    public function withPendingTasks(): self
    {
        $this->query->whereHas('assignedTasks', function (Builder $query) {
            $query->where('status', '!=', 'completed');
        });
        return $this;
    }

    /**
     * Get users with overdue tasks.
     *
     * @return $this
     */
    public function withOverdueTasks(): self
    {
        $this->query->whereHas('assignedTasks', function (Builder $query) {
            $query->overdue();
        });
        return $this;
    }

    /**
     * Get users who uploaded files.
     *
     * @return $this
     */
    public function withUploadedFiles(): self
    {
        $this->query->whereHas('uploadedFiles');
        return $this;
    }

    /**
     * Get users with activity logs.
     *
     * @return $this
     */
    public function withActivity(): self
    {
        $this->query->whereHas('activities');
        return $this;
    }

    /**
     * Get users sorted by last login.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByLastLogin(string $direction = 'desc'): self
    {
        $this->query->orderBy('last_login_at', $direction);
        return $this;
    }

    /**
     * Get users sorted by name.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByName(string $direction = 'asc'): self
    {
        $this->query->orderBy('first_name', $direction)
                   ->orderBy('last_name', $direction);
        return $this;
    }

    /**
     * Get users sorted by creation date.
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
     * Get users with their roles.
     *
     * @return $this
     */
    public function withRoles(): self
    {
        $this->query->with('roles');
        return $this;
    }

    /**
     * Get users with their permissions.
     *
     * @return $this
     */
    public function withPermissions(): self
    {
        $this->query->with('permissions');
        return $this;
    }

    /**
     * Get users with their projects.
     *
     * @return $this
     */
    public function withProjects(): self
    {
        $this->query->with(['clientProjects', 'managedProjects']);
        return $this;
    }

    /**
     * Get users with their tasks.
     *
     * @return $this
     */
    public function withTasks(): self
    {
        $this->query->with(['assignedTasks', 'createdTasks']);
        return $this;
    }

    /**
     * Get users with their files.
     *
     * @return $this
     */
    public function withFiles(): self
    {
        $this->query->with('uploadedFiles');
        return $this;
    }

    /**
     * Get users with their activity logs.
     *
     * @return $this
     */
    public function withActivityLogs(): self
    {
        $this->query->with('activities');
        return $this;
    }

    /**
     * Get users with their notifications.
     *
     * @return $this
     */
    public function withNotifications(): self
    {
        $this->query->with('notifications');
        return $this;
    }
}
