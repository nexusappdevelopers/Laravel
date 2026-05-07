<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // Queue cleanup every hour
        $schedule->command('queue:prune-failed')->hourly();
        $schedule->command('queue:prune-batches')->daily();
        
        // Cache cleanup daily
        $schedule->command('cache:prune-stale-tags')->daily();
        
        // Database backup daily at 2 AM
        $schedule->command('db:backup')->dailyAt('02:00');
        
        // Log cleanup weekly
        $schedule->command('log:clear')->weekly();
        
        // Generate reports weekly
        $schedule->command('reports:generate')->weekly()->mondays()->at('09:00');
        
        // Health checks every 5 minutes
        $schedule->command('health:check')->everyFiveMinutes();
        
        // Send email summaries daily
        $schedule->command('email:send-daily-summary')->dailyAt('18:00');
        
        // Cleanup temporary files daily
        $schedule->command('cleanup:temp-files')->daily();
        
        // Update statistics hourly
        $schedule->command('statistics:update')->hourly();
        
        // Check for overdue tasks every hour
        $schedule->command('tasks:check-overdue')->hourly();
        
        // Generate sitemap daily
        $schedule->command('sitemap:generate')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): array
    {
        return [
            // Project Installation
            \App\Console\Commands\InstallProject::class,
            
            // User Management
            \App\Console\Commands\CreateUser::class,
            \App\Console\Commands\DeleteUser::class,
            \App\Console\Commands\AssignRole::class,
            
            // Project Management
            \App\Console\Commands\CreateProject::class,
            \App\Console\Commands\ArchiveProject::class,
            \App\Console\Commands\UpdateProjectStatus::class,
            
            // Task Management
            \App\Console\Commands\CreateTask::class,
            \App\Console\Commands\AssignTask::class,
            \App\Console\Commands\UpdateTaskStatus::class,
            
            // Database Management
            \App\Console\Commands\DatabaseBackup::class,
            \App\Console\Commands\DatabaseRestore::class,
            \App\Console\Commands\DatabaseSeed::class,
            
            // Cache Management
            \App\Console\Commands\CacheClear::class,
            \App\Console\Commands\CacheWarmup::class,
            
            // Queue Management
            \App\Console\Commands\QueueRestart::class,
            \App\Console\Commands\QueueFailed::class,
            \App\Console\Commands\QueueRetry::class,
            
            // File Management
            \App\Console\Commands\CleanupFiles::class,
            \App\Console\Commands\GenerateThumbnails::class,
            \App\Console\Commands\CompressOldFiles::class,
            
            // Reporting
            \App\Console\Commands\GenerateReports::class,
            \App\Console\Commands\ExportData::class,
            \App\Console\Commands\AnalyticsReport::class,
            
            // Maintenance
            \App\Console\Commands\MaintenanceMode::class,
            \App\Console\Commands\SystemHealth::class,
            \App\Console\Commands\PerformanceCheck::class,
            
            // Notifications
            \App\Console\Commands\SendNotifications::class,
            \App\Console\Commands\EmailDigest::class,
            \App\Console\Commands\SmsAlerts::class,
            
            // Security
            \App\Console\Commands\SecurityAudit::class,
            \App\Console\Commands\PasswordReset::class,
            \App\Console\Commands\CleanSessions::class,
            
            // Development
            \App\Console\Commands\GenerateTestData::class,
            \App\Console\Commands\RefreshDatabase::class,
            \App\Console\Commands\ResetApplication::class,
        ];
    }
}
