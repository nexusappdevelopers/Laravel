<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
});

// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile Routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('profile');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/upload-avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.upload.avatar');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('profile.change.password');
    });
    
    // Settings Routes (can be expanded)
    Route::prefix('settings')->group(function () {
        Route::get('/', function () {
            return view('settings.index');
        })->name('settings');
        
        Route::get('/account', function () {
            return view('settings.account');
        })->name('settings.account');
        
        Route::get('/security', function () {
            return view('settings.security');
        })->name('settings.security');
        
        Route::get('/notifications', function () {
            return view('settings.notifications');
        })->name('settings.notifications');
    });
    
    // Admin Routes (protected by admin middleware)
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
        
        Route::get('/users', function () {
            return view('admin.users');
        })->name('admin.users');
        
        Route::get('/projects', function () {
            return view('admin.projects');
        })->name('admin.projects');
        
        Route::get('/tasks', function () {
            return view('admin.tasks');
        })->name('admin.tasks');
        
        Route::get('/companies', function () {
            return view('admin.companies');
        })->name('admin.companies');
        
        Route::get('/reports', function () {
            return view('admin.reports');
        })->name('admin.reports');
        
        Route::get('/settings', function () {
            return view('admin.settings');
        })->name('admin.settings');
    });
});

// Social Authentication Routes
Route::middleware('guest')->prefix('auth')->group(function () {
    Route::get('/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/github', [AuthController::class, 'redirectToGithub'])->name('auth.github');
    Route::get('/github/callback', [AuthController::class, 'handleGithubCallback'])->name('auth.github.callback');
});

// Email Verification Routes
Route::middleware('auth')->prefix('email')->group(function () {
    Route::get('/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify');
    Route::post('/resend', [AuthController::class, 'resend'])->name('verification.resend');
});

// Health Check Route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
        'environment' => config('app.env'),
    ]);
});

// Sitemap Route
Route::get('/sitemap.xml', function () {
    $sitemap = app('sitemap');
    return response($sitemap->render('xml'), 200, [
        'Content-Type' => 'application/xml'
    ]);
});
