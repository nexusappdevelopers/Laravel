<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\FileController;
use App\Http\Controllers\API\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('send-email-verification', [AuthController::class, 'sendEmailVerification']);
        Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail']);
    });

    // Protected Routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Profile Routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [AuthController::class, 'profile']);
            Route::put('/', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
            Route::post('upload-avatar', [AuthController::class, 'uploadAvatar']);
        });

        // Dashboard Routes
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('/statistics', [DashboardController::class, 'statistics']);
            Route::get('/charts', [DashboardController::class, 'charts']);
            Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);
        });

        // User Routes
        Route::apiResource('users', UserController::class)->except(['create', 'edit']);
        Route::prefix('users')->group(function () {
            Route::post('{id}/toggle-active', [UserController::class, 'toggleActive']);
            Route::get('statistics', [UserController::class, 'statistics']);
            Route::get('export', [UserController::class, 'export']);
            Route::delete('bulk-delete', [UserController::class, 'bulkDelete']);
        });

        // Company Routes
        Route::apiResource('companies', CompanyController::class)->except(['create', 'edit']);
        Route::prefix('companies')->group(function () {
            Route::get('statistics', [CompanyController::class, 'statistics']);
            Route::get('export', [CompanyController::class, 'export']);
        });

        // Project Routes
        Route::apiResource('projects', ProjectController::class)->except(['create', 'edit']);
        Route::prefix('projects')->group(function () {
            Route::post('{id}/change-status', [ProjectController::class, 'changeStatus']);
            Route::post('{id}/update-progress', [ProjectController::class, 'updateProgress']);
            Route::post('{id}/assign-manager', [ProjectController::class, 'assignManager']);
            Route::post('{id}/add-team-member', [ProjectController::class, 'addTeamMember']);
            Route::post('{id}/remove-team-member', [ProjectController::class, 'removeTeamMember']);
            Route::get('statistics', [ProjectController::class, 'statistics']);
            Route::get('export', [ProjectController::class, 'export']);
            Route::get('active', [ProjectController::class, 'getActiveProjects']);
            Route::get('completed', [ProjectController::class, 'getCompletedProjects']);
            Route::get('overdue', [ProjectController::class, 'getOverdueProjects']);
            Route::get('ending-soon', [ProjectController::class, 'getEndingSoonProjects']);
        });

        // Task Routes
        Route::apiResource('tasks', TaskController::class)->except(['create', 'edit']);
        Route::prefix('tasks')->group(function () {
            Route::post('{id}/change-status', [TaskController::class, 'changeStatus']);
            Route::post('{id}/assign', [TaskController::class, 'assignTask']);
            Route::get('my-tasks', [TaskController::class, 'myTasks']);
            Route::get('statistics', [TaskController::class, 'statistics']);
            Route::get('export', [TaskController::class, 'export']);
            Route::get('overdue', [TaskController::class, 'getOverdueTasks']);
            Route::get('due-today', [TaskController::class, 'getDueTodayTasks']);
            Route::get('due-this-week', [TaskController::class, 'getDueThisWeekTasks']);
        });

        // File Routes
        Route::prefix('files')->group(function () {
            Route::get('/', [FileController::class, 'index']);
            Route::post('upload', [FileController::class, 'upload']);
            Route::get('{id}', [FileController::class, 'show']);
            Route::get('{id}/download', [FileController::class, 'download']);
            Route::delete('{id}', [FileController::class, 'destroy']);
            Route::delete('bulk-delete', [FileController::class, 'bulkDelete']);
        });

        // Notification Routes
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
            Route::post('mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
            Route::delete('{id}', [NotificationController::class, 'destroy']);
            Route::delete('clear-all', [NotificationController::class, 'clearAll']);
        });

        // Activity Log Routes
        Route::prefix('activity')->group(function () {
            Route::get('/', [ActivityController::class, 'index']);
            Route::get('user/{userId}', [ActivityController::class, 'userActivity']);
            Route::get('project/{projectId}', [ActivityController::class, 'projectActivity']);
            Route::get('task/{taskId}', [ActivityController::class, 'taskActivity']);
        });

        // Search Routes
        Route::prefix('search')->group(function () {
            Route::get('/global', [SearchController::class, 'global']);
            Route::get('/users', [SearchController::class, 'users']);
            Route::get('/projects', [SearchController::class, 'projects']);
            Route::get('/tasks', [SearchController::class, 'tasks']);
            Route::get('/companies', [SearchController::class, 'companies']);
        });

        // Settings Routes
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index']);
            Route::put('/general', [SettingsController::class, 'updateGeneral']);
            Route::put('/email', [SettingsController::class, 'updateEmail']);
            Route::put('/security', [SettingsController::class, 'updateSecurity']);
            Route::put('/notifications', [SettingsController::class, 'updateNotifications']);
        });

        // Reports Routes
        Route::prefix('reports')->group(function () {
            Route::get('/users', [ReportController::class, 'users']);
            Route::get('/projects', [ReportController::class, 'projects']);
            Route::get('/tasks', [ReportController::class, 'tasks']);
            Route::get('/activity', [ReportController::class, 'activity']);
            Route::get('/export/{type}', [ReportController::class, 'export']);
        });
    });

    // Public Routes (no authentication required)
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
        ]);
    });

    Route::get('/info', function () {
        return response()->json([
            'name' => config('app.name'),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ]);
    });
});

// Rate limiting for API
Route::middleware('throttle:60,1')->group(function () {
    // Apply rate limiting to authentication routes
    Route::prefix('api/v1/auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    });
});

// API Documentation Routes
Route::get('/docs', function () {
    return response()->json([
        'message' => 'API Documentation',
        'endpoints' => [
            'authentication' => '/api/v1/auth/*',
            'users' => '/api/v1/users',
            'projects' => '/api/v1/projects',
            'tasks' => '/api/v1/tasks',
            'companies' => '/api/v1/companies',
            'files' => '/api/v1/files',
            'notifications' => '/api/v1/notifications',
            'dashboard' => '/api/v1/dashboard',
            'search' => '/api/v1/search',
            'reports' => '/api/v1/reports',
        ],
        'documentation' => env('APP_URL') . '/docs/api',
        'swagger' => env('APP_URL') . '/api/documentation',
    ]);
});
