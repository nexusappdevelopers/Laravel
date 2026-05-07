<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\LoginRequest;
use App\Http\Requests\API\RegisterRequest;
use App\Http\Requests\API\ForgotPasswordRequest;
use App\Http\Requests\API\ResetPasswordRequest;
use App\Http\Requests\API\ChangePasswordRequest;
use App\Http\Resources\API\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * The user service instance.
     *
     * @var UserService
     */
    protected UserService $userService;

    /**
     * Create a new controller instance.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth:sanctum')->only(['logout', 'profile', 'changePassword', 'refresh']);
    }

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());
            
            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Send email verification notification
            if (!$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
            }
            
            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'User registered successfully', 201);
            
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Registration failed. Please try again.', 500);
        }
    }

    /**
     * Login user and create token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');
            
            if (!Auth::attempt($credentials)) {
                return $this->errorResponse('Invalid credentials', 401);
            }
            
            $user = Auth::user();
            
            if (!$user->is_active) {
                return $this->errorResponse('Your account is deactivated', 403);
            }
            
            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);
            
            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Login successful');
            
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Login failed. Please try again.', 500);
        }
    }

    /**
     * Logout user (revoke token).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            return $this->successResponse([], 'Logout successful');
            
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Logout failed. Please try again.', 500);
        }
    }

    /**
     * Get authenticated user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['roles', 'permissions']);
            
            return $this->successResponse([
                'user' => new UserResource($user),
            ], 'Profile retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Profile retrieval failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to retrieve profile', 500);
        }
    }

    /**
     * Update user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validatedData = $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'bio' => 'nullable|string|max:1000',
            ]);
            
            $updatedUser = $this->userService->update($user->id, $validatedData);
            
            return $this->successResponse([
                'user' => new UserResource($updatedUser),
            ], 'Profile updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to update profile', 500);
        }
    }

    /**
     * Change user password.
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $this->userService->changePassword(
                $user->id,
                $request->current_password,
                $request->password
            );
            
            return $this->successResponse([], 'Password changed successfully');
            
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to change password', 500);
        }
    }

    /**
     * Send password reset link.
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink($request->only('email'));
            
            if ($status === Password::RESET_LINK_SENT) {
                return $this->successResponse([], 'Password reset link sent successfully');
            }
            
            return $this->errorResponse('Unable to send password reset link', 422);
            
        } catch (\Exception $e) {
            Log::error('Password reset request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to send password reset link', 500);
        }
    }

    /**
     * Reset password.
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset($request->validated(), function ($user, $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();
                
                event(new PasswordReset($user));
            });
            
            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse([], 'Password reset successful');
            }
            
            return $this->errorResponse('Invalid reset token', 422);
            
        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to reset password', 500);
        }
    }

    /**
     * Refresh authentication token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Revoke current token
            $request->user()->currentAccessToken()->delete();
            
            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Token refreshed successfully');
            
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to refresh token', 500);
        }
    }

    /**
     * Send email verification.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendEmailVerification(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user->hasVerifiedEmail()) {
                return $this->errorResponse('Email already verified', 422);
            }
            
            $user->sendEmailVerificationNotification();
            
            return $this->successResponse([], 'Email verification link sent successfully');
            
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to send email verification', 500);
        }
    }

    /**
     * Verify email.
     *
     * @param Request $request
     * @param string $id
     * @param string $hash
     * @return JsonResponse
     */
    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($id);
            
            if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
                return $this->errorResponse('Invalid verification link', 422);
            }
            
            if ($user->hasVerifiedEmail()) {
                return $this->errorResponse('Email already verified', 422);
            }
            
            $user->markEmailAsVerified();
            
            return $this->successResponse([], 'Email verified successfully');
            
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to verify email', 500);
        }
    }
}
