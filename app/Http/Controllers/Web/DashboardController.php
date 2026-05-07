<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get statistics
        $statistics = $this->getDashboardStatistics();
        
        // Get recent activity
        $recentActivities = $this->getRecentActivity();
        
        // Get recent projects
        $recentProjects = $this->getRecentProjects();
        
        // Get upcoming tasks
        $upcomingTasks = $this->getUpcomingTasks();
        
        return view('dashboard', compact(
            'statistics',
            'recentActivities',
            'recentProjects',
            'upcomingTasks',
            'user'
        ));
    }

    /**
     * Get dashboard statistics.
     *
     * @return array
     */
    protected function getDashboardStatistics(): array
    {
        $user = Auth::user();
        
        // User statistics
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->count();
        
        // Project statistics
        $totalProjects = Project::count();
        $activeProjects = Project::whereIn('status', ['planning', 'in_progress'])->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $overdueProjects = Project::whereDate('end_date', '<', now())
                                       ->where('status', '!=', 'completed')
                                       ->count();
        
        // Task statistics
        $totalTasks = Task::count();
        $activeTasks = Task::whereIn('status', ['todo', 'in_progress', 'review'])->count();
        $completedTasks = Task::where('status', 'completed')->count();
        $overdueTasks = Task::whereDate('due_date', '<', now())
                                   ->where('status', '!=', 'completed')
                                   ->count();
        
        // Team statistics
        $teamMembers = User::whereHas('assignedTasks')->count();
        
        // Revenue statistics
        $totalRevenue = Project::sum('budget') ?? 0;
        
        // Files statistics
        $filesUploaded = DB::table('files')->count();
        
        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'overdue_projects' => $overdueProjects,
            'total_tasks' => $totalTasks,
            'active_tasks' => $activeTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'new_users' => $newUsersThisMonth,
            'team_members' => $teamMembers,
            'total_revenue' => $totalRevenue,
            'files_uploaded' => $filesUploaded,
        ];
    }

    /**
     * Get recent activity.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRecentActivity()
    {
        return DB::table('activity_logs')
                   ->join('users', 'activity_logs.causer_id', '=', 'users.id')
                   ->select('activity_logs.*', 'users.first_name', 'users.last_name', 'users.avatar')
                   ->orderBy('activity_logs.created_at', 'desc')
                   ->limit(10)
                   ->get();
    }

    /**
     * Get recent projects.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRecentProjects()
    {
        return Project::with(['client', 'projectManager'])
                     ->orderBy('created_at', 'desc')
                     ->limit(5)
                     ->get();
    }

    /**
     * Get upcoming tasks.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getUpcomingTasks()
    {
        return Task::with(['project', 'assignee'])
                     ->whereDate('due_date', '>=', now())
                     ->whereDate('due_date', '<=', now()->addDays(7))
                     ->where('status', '!=', 'completed')
                     ->orderBy('due_date', 'asc')
                     ->limit(10)
                     ->get();
    }

    /**
     * Get dashboard charts data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function charts(Request $request)
    {
        $period = $request->get('period', 'month'); // month, quarter, year
        
        $data = $this->getChartData($period);
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get chart data for dashboard.
     *
     * @param string $period
     * @return array
     */
    protected function getChartData(string $period): array
    {
        $now = now();
        
        switch ($period) {
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
                
            case 'quarter':
                $quarter = ceil($now->month / 3);
                $startDate = $now->copy()->startOfQuarter();
                $endDate = $now->copy()->endOfQuarter();
                break;
                
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
                
            default:
                $startDate = $now->copy()->subMonth();
                $endDate = $now;
                break;
        }
        
        // Project progress over time
        $projectProgress = Project::whereBetween('created_at', [$startDate, $endDate])
                                   ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(progress_percentage) / COUNT(*) as avg_progress')
                                   ->groupBy('DATE(created_at)')
                                   ->orderBy('date')
                                   ->get();
        
        // Task completion over time
        $taskCompletion = Task::whereBetween('completed_at', [$startDate, $endDate])
                                 ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
                                 ->where('status', 'completed')
                                 ->groupBy('DATE(completed_at)')
                                 ->orderBy('date')
                                 ->get();
        
        // User registration over time
        $userRegistration = User::whereBetween('created_at', [$startDate, $endDate])
                                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                ->groupBy('DATE(created_at)')
                                ->orderBy('date')
                                ->get();
        
        return [
            'project_progress' => $projectProgress->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                    'avg_progress' => round($item->avg_progress, 2),
                ];
            }),
            'task_completion' => $taskCompletion->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            }),
            'user_registration' => $userRegistration->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            }),
        ];
    }

    /**
     * Get recent activity for dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentActivity(Request $request)
    {
        $limit = $request->get('limit', 20);
        
        $activities = DB::table('activity_logs')
                       ->join('users', 'activity_logs.causer_id', '=', 'users.id')
                       ->select('activity_logs.*', 'users.first_name', 'users.last_name', 'users.avatar')
                       ->orderBy('activity_logs.created_at', 'desc')
                       ->limit($limit)
                       ->get();
        
        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get dashboard statistics as JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $statistics = $this->getDashboardStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}
