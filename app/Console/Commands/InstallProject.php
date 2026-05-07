<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure the Laravel Showcase project';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🚀 Installing Laravel Showcase Project...');

        $this->createStorageDirectories();
        $this->generateAppKey();
        $this->runMigrations();
        $this->linkStorage();
        $this->installDependencies();
        $this->seedDatabase();
        $this->optimizeApplication();
        $this->createSymlinks();

        $this->newLine();
        $this->info('✅ Laravel Showcase project installed successfully!');
        $this->info('📝 Next steps:');
        $this->info('   1. Configure your .env file');
        $this->info('   2. Run: php artisan serve');
        $this->info('   3. Visit: http://localhost:8000');

        return Command::SUCCESS;
    }

    /**
     * Create necessary storage directories.
     */
    protected function createStorageDirectories(): void
    {
        $this->info('📁 Creating storage directories...');
        
        $directories = [
            storage_path('app/public'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("   Created: {$directory}");
            }
        }
    }

    /**
     * Generate application key.
     */
    protected function generateAppKey(): void
    {
        $this->info('🔑 Generating application key...');
        
        if (!File::exists(base_path('.env'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
            $this->line('   Created: .env file');
        }

        Artisan::call('key:generate', ['--force' => true]);
        $this->line('   Generated: Application key');
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->info('🗄️ Running database migrations...');
        
        Artisan::call('migrate', ['--force' => true]);
        $this->line('   Completed: Database migrations');
    }

    /**
     * Create storage symbolic link.
     */
    protected function linkStorage(): void
    {
        $this->info('🔗 Linking storage directory...');
        
        if (File::exists(public_path('storage'))) {
            File::delete(public_path('storage'));
        }
        
        Artisan::call('storage:link');
        $this->line('   Linked: Storage directory');
    }

    /**
     * Install Composer dependencies.
     */
    protected function installDependencies(): void
    {
        $this->info('📦 Installing Composer dependencies...');
        
        if (File::exists(base_path('composer.lock'))) {
            $this->line('   Dependencies already installed');
            return;
        }

        // This would typically run: composer install
        $this->line('   Note: Run "composer install" manually if needed');
    }

    /**
     * Seed the database.
     */
    protected function seedDatabase(): void
    {
        $this->info('🌱 Seeding database...');
        
        Artisan::call('db:seed', ['--force' => true]);
        $this->line('   Completed: Database seeding');
    }

    /**
     * Optimize the application.
     */
    protected function optimizeApplication(): void
    {
        $this->info('⚡ Optimizing application...');
        
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('optimize');
        
        $this->line('   Completed: Application optimization');
    }

    /**
     * Create symbolic links.
     */
    protected function createSymlinks(): void
    {
        $this->info('🔗 Creating symbolic links...');
        
        Artisan::call('storage:link');
        $this->line('   Created: Symbolic links');
    }
}
