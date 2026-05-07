<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->disableForeignKeyChecks();
        
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            CompanySeeder::class,
            ProjectSeeder::class,
            TaskSeeder::class,
        ]);
        
        $this->enableForeignKeyChecks();
    }

    /**
     * Disable foreign key checks.
     */
    protected function disableForeignKeyChecks(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    }

    /**
     * Enable foreign key checks.
     */
    protected function enableForeignKeyChecks(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
