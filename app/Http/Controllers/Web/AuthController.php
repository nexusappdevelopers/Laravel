<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Http\Requests\Web\RegisterRequest;
use App\Http\Requests\Web\ForgotPasswordRequest;
use App\Http\Requests\Web\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\PasswordReset;
use App\Notifications\WelcomeEmailNotification;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(LoginRequest $request)
    {
        $request->authenticate();
        
        $request->session()->regenerate();
        
        event(new Login($request->user(), false));
        
        return redirect()->intended(route('dashboard'))
                    ->with('success', 'Welcome back, ' . $request->user()->first_name . '!');
    }

    /**
     * Show the registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'bio' => $request->bio,
        ]);

        // Assign default role
        $user->assignRole('user');

        event(new Registered($user));
        
        // Send welcome email
        $user->notify(new WelcomeEmailNotification());

        Auth::login($user);
        
        return redirect()->route('dashboard')
                    ->with('success', 'Registration successful! Welcome to our platform.');
    }

    /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')
                    ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show the forgot password form.
     *
     * @return \Illuminate\View\View
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle a forgot password request.
     *
     * @param ForgotPasswordRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()
                    ->withErrors(['email' => 'We cannot find a user with that email address.'])
                    ->withInput();
        }

        // Generate reset token
        $token = Str::random(60);
        
        // Save token to database
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        event(new PasswordResetLinkSent($user, $token));
        
        // Send password reset email
        $user->notify(new PasswordResetNotification($token));

        return back()
                    ->with('success', 'Password reset link has been sent to your email address.');
    }

    /**
     * Show the password reset form.
     *
     * @param string $token
     * @return \Illuminate\View\View
     */
    public function showResetForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Handle a password reset request.
     *
     * @param ResetPasswordRequest $request
     * @param string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(ResetPasswordRequest $request, $token)
    {
        $resetToken = DB::table('password_reset_tokens')
                            ->where('token', $token)
                            ->where('created_at', '>', now()->subHours(2))
                            ->first();

        if (!$resetToken) {
            return back()
                    ->withErrors(['email' => 'This password reset token is invalid or has expired.'])
                    ->withInput();
        }

        $user = User::where('email', $resetToken->email)->first();
        
        if (!$user) {
            return back()
                    ->withErrors(['email' => 'We cannot find a user with that email address.'])
                    ->withInput();
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'remember_token' => null,
        ]);

        // Delete the reset token
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        event(new PasswordReset($user));

        return redirect()->route('login')
                    ->with('success', 'Your password has been reset successfully.');
    }

    /**
     * Redirect to Google OAuth.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();
            
            if ($user) {
                // Link account to existing user
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
                
                Auth::login($user);
                
                return redirect()->route('dashboard')
                            ->with('success', 'Google account linked successfully!');
            } else {
                // Create new user
                $user = User::create([
                    'first_name' => $googleUser->user['given_name'] ?? 'Google',
                    'last_name' => $googleUser->user['family_name'] ?? 'User',
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(40)),
                    'email_verified_at' => now(),
                ]);
                
                $user->assignRole('user');
                
                Auth::login($user);
                
                return redirect()->route('dashboard')
                            ->with('success', 'Welcome! Your Google account has been linked successfully.');
            }
            
        } catch (\Exception $e) {
            return redirect()->route('login')
                        ->with('error', 'Something went wrong with Google login. Please try again.');
        }
    }

    /**
     * Redirect to GitHub OAuth.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGithub()
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Handle GitHub OAuth callback.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGithubCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->user();
            
            $user = User::where('email', $githubUser->getEmail())->first();
            
            if ($user) {
                // Link account to existing user
                $user->update([
                    'github_id' => $githubUser->getId(),
                    'avatar' => $githubUser->getAvatar(),
                ]);
                
                Auth::login($user);
                
                return redirect()->route('dashboard')
                            ->with('success', 'GitHub account linked successfully!');
            } else {
                // Create new user
                $user = User::create([
                    'first_name' => $githubUser->getNickname() ?? 'GitHub',
                    'last_name' => 'User',
                    'email' => $githubUser->getEmail(),
                    'avatar' => $githubUser->getAvatar(),
                    'password' => Hash::make(Str::random(40)),
                    'email_verified_at' => now(),
                ]);
                
                $user->assignRole('user');
                
                Auth::login($user);
                
                return redirect()->route('dashboard')
                            ->with('success', 'Welcome! Your GitHub account has been linked successfully.');
            }
            
        } catch (\Exception $e) {
            return redirect()->route('login')
                        ->with('error', 'Something went wrong with GitHub login. Please try again.');
        }
    }

    /**
     * Handle email verification.
     *
     * @param int $id
     * @param string $hash
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyEmail($id, $hash)
    {
        $user = User::findOrFail($id);
        
        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')
                        ->with('error', 'Invalid verification link.');
        }
        
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')
                        ->with('info', 'Your email is already verified.');
        }
        
        $user->markEmailAsVerified();
        
        return redirect()->route('login')
                        ->with('success', 'Your email has been verified successfully!');
    }

    /**
     * Resend email verification.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()
                        ->withErrors(['email' => 'We cannot find a user with that email address.']);
        }
        
        if ($user->hasVerifiedEmail()) {
            return back()
                        ->with('info', 'Your email is already verified.');
        }
        
        // Send verification email
        $user->sendEmailVerificationNotification();
        
        return back()
                    ->with('success', 'Verification email has been sent to your email address.');
    }
}
