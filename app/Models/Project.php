<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'requirements',
        'status',
        'priority',
        'budget',
        'start_date',
        'end_date',
        'company_id',
        'client_id',
        'project_manager_id',
        'team_members',
        'repository_url',
        'demo_url',
        'production_url',
        'notes',
        'progress_percentage',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'team_members' => 'array',
        'progress_percentage' => 'integer',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'team_members',
    ];

    /**
     * Get the attributes that should be logged.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'status', 'priority', 'progress_percentage'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the project's duration in days.
     *
     * @return int|null
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }

        return null;
    }

    /**
     * Get the project's status color.
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'planning' => 'blue',
            'in_progress' => 'green',
            'on_hold' => 'yellow',
            'completed' => 'purple',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get the project's priority color.
     *
     * @return string
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray',
        };
    }

    /**
     * Scope a query to filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by priority.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $priority
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to search projects by name or description.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to include projects where the user is involved.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInvolvingUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('client_id', $userId)
              ->orWhere('project_manager_id', $userId)
              ->orWhereJsonContains('team_members', $userId);
        });
    }

    /**
     * Get the company for the project.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client for the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the project manager for the project.
     */
    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    /**
     * Get the user who created the project.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the completed tasks for the project.
     */
    public function completedTasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('status', 'completed');
    }

    /**
     * Get the pending tasks for the project.
     */
    public function pendingTasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('status', '!=', 'completed');
    }

    /**
     * Get the files for the project.
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'fileable_id')
                    ->where('fileable_type', Project::class);
    }

    /**
     * Get the team members for the project.
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_team_members');
    }

    /**
     * Get the project's activity logs.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(\Spatie\Activitylog\Models\Activity::class, 'subject_id')
                    ->where('subject_type', Project::class);
    }

    /**
     * Check if the project is overdue.
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        return $this->end_date && $this->end_date->isPast() && $this->status !== 'completed';
    }

    /**
     * Calculate the project progress based on tasks.
     *
     * @return int
     */
    public function calculateProgress(): int
    {
        $totalTasks = $this->tasks()->count();
        $completedTasks = $this->completedTasks()->count();

        if ($totalTasks === 0) {
            return $this->progress_percentage;
        }

        return (int) (($completedTasks / $totalTasks) * 100);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            $project->id = Str::uuid();
            $project->slug = Str::slug($project->name);
        });

        static::updating(function ($project) {
            if ($project->isDirty('name')) {
                $project->slug = Str::slug($project->name);
            }

            // Update progress percentage when status changes to completed
            if ($project->isDirty('status') && $project->status === 'completed') {
                $project->progress_percentage = 100;
            }
        });
    }
}
