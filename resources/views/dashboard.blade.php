@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-description">Welcome back, {{ Auth::user()->first_name }}! Here's what's happening with your projects.</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Projects</span>
            <div class="stat-icon primary">
                <i class="fas fa-project-diagram"></i>
            </div>
        </div>
        <div class="stat-value">{{ $statistics['total_projects'] ?? 0 }}</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 12% from last month
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Active Tasks</span>
            <div class="stat-icon warning">
                <i class="fas fa-tasks"></i>
            </div>
        </div>
        <div class="stat-value">{{ $statistics['active_tasks'] ?? 0 }}</div>
        <div class="stat-change negative">
            <i class="fas fa-arrow-down"></i> 5% from last week
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Completed Tasks</span>
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="stat-value">{{ $statistics['completed_tasks'] ?? 0 }}</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 18% from last month
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Team Members</span>
            <div class="stat-icon info">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="stat-value">{{ $statistics['team_members'] ?? 0 }}</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 8% from last month
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Overdue Projects</span>
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        <div class="stat-value">{{ $statistics['overdue_projects'] ?? 0 }}</div>
        <div class="stat-change negative">
            <i class="fas fa-arrow-up"></i> 2 new this week
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Revenue</span>
            <div class="stat-icon success">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-value">${{ number_format($statistics['total_revenue'] ?? 0, 0) }}</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 24% from last month
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">New Users</span>
            <div class="stat-icon info">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>
        <div class="stat-value">{{ $statistics['new_users'] ?? 0 }}</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 15% from last month
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Files Uploaded</span>
            <div class="stat-icon primary">
                <i class="fas fa-file-upload"></i>
            </div>
        </div>
        <div class="stat-value">{{ $statistics['files_uploaded'] ?? 0 }}</div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 32% from last month
        </div>
    </div>
</div>

<!-- Charts Section -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Project Progress</h3>
        </div>
        <div class="card-body">
            <canvas id="projectProgressChart" width="400" height="200"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Task Distribution</h3>
        </div>
        <div class="card-body">
            <canvas id="taskDistributionChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
    </div>
    <div class="card-body">
        @if($recentActivities->count() > 0)
            <div class="activity-list">
                @foreach($recentActivities as $activity)
                    <div style="display: flex; align-items: start; padding: 1rem 0; border-bottom: 1px solid var(--border);">
                        <div style="flex-shrink: 0;">
                            <img src="{{ $activity->causer->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($activity->causer->full_name) . '&color=7F9CF5&background=EBF4FF' }}" 
                                 alt="{{ $activity->causer->full_name }}" 
                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        </div>
                        <div style="margin-left: 1rem; flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.25rem;">
                                <div>
                                    <strong>{{ $activity->causer->full_name }}</strong>
                                    <span class="badge badge-primary" style="margin-left: 0.5rem;">{{ $activity->subject_type }}</span>
                                </div>
                                <small style="color: var(--gray);">{{ $activity->created_at->diffForHumans() }}</small>
                            </div>
                            <p style="margin: 0; color: var(--dark);">{{ $activity->description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="text-align: center; color: var(--gray); padding: 2rem;">No recent activity found.</p>
        @endif
    </div>
</div>

<!-- Recent Projects -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Projects</h3>
    </div>
    <div class="card-body">
        @if($recentProjects->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentProjects as $project)
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-project-diagram" style="color: var(--primary);"></i>
                                        <a href="{{ route('projects.show', $project->id) }}" style="color: var(--dark); text-decoration: none; font-weight: 500;">
                                            {{ $project->name }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <img src="{{ $project->client->avatar_url }}" 
                                             alt="{{ $project->client->full_name }}" 
                                             style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                                        <span>{{ $project->client->full_name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $project->status_color }}">
                                        {{ ucfirst($project->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="flex: 1; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                                            <div style="width: {{ $project->progress_percentage }}%; height: 100%; background: var(--success);"></div>
                                        </div>
                                        <small style="color: var(--gray);">{{ $project->progress_percentage }}%</small>
                                    </div>
                                </td>
                                <td>
                                    @if($project->end_date)
                                        <span style="color: {{ $project->isOverdue() ? 'var(--danger)' : 'var(--dark)' }};">
                                            {{ $project->end_date->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span style="color: var(--gray);">No due date</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="text-align: center; color: var(--gray); padding: 2rem;">No projects found.</p>
        @endif
    </div>
</div>

<!-- Upcoming Tasks -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Upcoming Tasks</h3>
    </div>
    <div class="card-body">
        @if($upcomingTasks->count() > 0)
            <div class="task-list">
                @foreach($upcomingTasks as $task)
                    <div style="display: flex; align-items: start; padding: 1rem 0; border-bottom: 1px solid var(--border);">
                        <div style="flex-shrink: 0;">
                            <div style="width: 40px; height: 40px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; background: {{ $task->priority_color === 'red' ? 'var(--danger)' : ($task->priority_color === 'orange' ? 'var(--warning)' : 'var(--primary)') }}; color: white;">
                                <i class="fas fa-flag"></i>
                            </div>
                        </div>
                        <div style="margin-left: 1rem; flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.25rem;">
                                <div>
                                    <strong>{{ $task->title }}</strong>
                                    @if($task->project)
                                        <span class="badge badge-primary" style="margin-left: 0.5rem;">{{ $task->project->name }}</span>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="badge badge-{{ $task->status_color }}">
                                        {{ ucfirst($task->status) }}
                                    </span>
                                    @if($task->isOverdue())
                                        <span class="badge badge-danger">Overdue</span>
                                    @endif
                                </div>
                            </div>
                            @if($task->due_date)
                                <p style="margin: 0.25rem 0 0 0; color: var(--gray); font-size: 0.875rem;">
                                    <i class="fas fa-calendar"></i> Due: {{ $task->due_date->format('M d, Y h:i A') }}
                                </p>
                            @endif
                            @if($task->assignee)
                                <p style="margin: 0; color: var(--gray); font-size: 0.875rem;">
                                    <i class="fas fa-user"></i> Assigned to: {{ $task->assignee->full_name }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="text-align: center; color: var(--gray); padding: 2rem;">No upcoming tasks found.</p>
        @endif
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Project Progress Chart
    const projectProgressCtx = document.getElementById('projectProgressChart').getContext('2d');
    new Chart(projectProgressCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Completed Projects',
                data: [12, 19, 15, 25, 22, 30],
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4
            }, {
                label: 'New Projects',
                data: [8, 12, 10, 14, 18, 16],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Task Distribution Chart
    const taskDistributionCtx = document.getElementById('taskDistributionChart').getContext('2d');
    new Chart(taskDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['To Do', 'In Progress', 'Review', 'Completed'],
            datasets: [{
                data: [15, 25, 10, 50],
                backgroundColor: [
                    'rgba(107, 114, 128, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(16, 185, 129, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
</script>
@endsection
