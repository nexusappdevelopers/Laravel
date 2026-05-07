<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService extends BaseService implements BaseServiceInterface
{
    /**
     * The validation rules for creation.
     *
     * @var array
     */
    protected array $createRules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'phone' => 'nullable|string|max:20',
        'date_of_birth' => 'nullable|date',
        'gender' => 'nullable|in:male,female,other',
        'bio' => 'nullable|string|max:1000',
        'is_active' => 'boolean',
    ];

    /**
     * The validation rules for update.
     *
     * @var array
     */
    protected array $updateRules = [
        'first_name' => 'sometimes|required|string|max:255',
        'last_name' => 'sometimes|required|string|max:255',
        'email' => 'sometimes|required|email|unique:users,email',
        'password' => 'sometimes|required|string|min:8|confirmed',
        'phone' => 'nullable|string|max:20',
        'date_of_birth' => 'nullable|date',
        'gender' => 'nullable|in:male,female,other',
        'bio' => 'nullable|string|max:1000',
        'is_active' => 'boolean',
    ];

    /**
     * Create a new service instance.
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        return $this->transaction(function () use ($data) {
            $validatedData = $this->validateCreate($data);
            $transformedData = $this->transformData($validatedData);
            
            $user = $this->repository->create($transformedData);
            
            // Assign default role if provided
            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            }
            
            // Log activity
            $this->logActivity('user_created', $user, [
                'name' => $user->full_name,
                'email' => $user->email,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\UserCreated($user));
            
            return $user;
        });
    }

    /**
     * Update a user.
     *
     * @param string|int $id
     * @param array $data
     * @return User
     */
    public function update($id, array $data): User
    {
        return $this->transaction(function () use ($id, $data) {
            $user = $this->repository->findOrFail($id);
            $validatedData = $this->validateUpdate($id, $data);
            $transformedData = $this->transformData($validatedData);
            
            $oldData = $user->toArray();
            $user->update($transformedData);
            
            // Update role if provided
            if (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            }
            
            // Log activity
            $this->logActivity('user_updated', $user, [
                'old_data' => $oldData,
                'new_data' => $transformedData,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\UserUpdated($user, $oldData));
            
            return $user;
        });
    }

    /**
     * Delete a user.
     *
     * @param string|int $id
     * @return bool
     */
    public function delete($id): bool
    {
        return $this->transaction(function () use ($id) {
            $user = $this->repository->findOrFail($id);
            
            // Log activity
            $this->logActivity('user_deleted', $user, [
                'name' => $user->full_name,
                'email' => $user->email,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\UserDeleted($user));
            
            return $this->repository->delete($id);
        });
    }

    /**
     * Upload avatar for user.
     *
     * @param string|int $id
     * @param \Illuminate\Http\UploadedFile $file
     * @return User
     */
    public function uploadAvatar($id, $file): User
    {
        return $this->transaction(function () use ($id, $file) {
            $user = $this->repository->findOrFail($id);
            
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            // Store new avatar
            $path = $file->store('avatars', 'public');
            
            $user->update(['avatar' => $path]);
            
            // Log activity
            $this->logActivity('avatar_uploaded', $user, [
                'avatar_path' => $path,
            ]);
            
            return $user;
        });
    }

    /**
     * Change user password.
     *
     * @param string|int $id
     * @param string $currentPassword
     * @param string $newPassword
     * @return User
     */
    public function changePassword($id, string $currentPassword, string $newPassword): User
    {
        return $this->transaction(function () use ($id, $currentPassword, $newPassword) {
            $user = $this->repository->findOrFail($id);
            
            // Verify current password
            if (!Hash::check($currentPassword, $user->password)) {
                throw new \InvalidArgumentException('Current password is incorrect');
            }
            
            $user->update(['password' => Hash::make($newPassword)]);
            
            // Log activity
            $this->logActivity('password_changed', $user);
            
            // Fire event
            $this->fireEvent(new \App\Events\PasswordChanged($user));
            
            return $user;
        });
    }

    /**
     * Reset user password.
     *
     * @param string|int $id
     * @param string $newPassword
     * @return User
     */
    public function resetPassword($id, string $newPassword): User
    {
        return $this->transaction(function () use ($id, $newPassword) {
            $user = $this->repository->findOrFail($id);
            
            $user->update(['password' => Hash::make($newPassword)]);
            
            // Log activity
            $this->logActivity('password_reset', $user);
            
            // Fire event
            $this->fireEvent(new \App\Events\PasswordReset($user));
            
            return $user;
        });
    }

    /**
     * Toggle user active status.
     *
     * @param string|int $id
     * @return User
     */
    public function toggleActive($id): User
    {
        return $this->transaction(function () use ($id) {
            $user = $this->repository->findOrFail($id);
            
            $user->update(['is_active' => !$user->is_active]);
            
            // Log activity
            $this->logActivity('user_status_toggled', $user, [
                'is_active' => $user->is_active,
            ]);
            
            // Fire event
            $this->fireEvent(new \App\Events\UserStatusToggled($user));
            
            return $user;
        });
    }

    /**
     * Get users by role.
     *
     * @param string $role
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->byRole($role)->paginate($perPage);
    }

    /**
     * Get active users.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActiveUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->active()->paginate($perPage);
    }

    /**
     * Get users with email verified.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getEmailVerifiedUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->emailVerified()->paginate($perPage);
    }

    /**
     * Get users who logged in within the last X days.
     *
     * @param int $days
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsersLoggedInLastDays(int $days, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->loggedInLastDays($days)->paginate($perPage);
    }

    /**
     * Get users by gender.
     *
     * @param string $gender
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByGender(string $gender, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->byGender($gender)->paginate($perPage);
    }

    /**
     * Get users with incomplete profiles.
     *
     * @param int $threshold
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsersWithIncompleteProfiles(int $threshold = 80, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->withIncompleteProfiles($threshold)->paginate($perPage);
    }

    /**
     * Get project managers.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProjectManagers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->projectManagers()->paginate($perPage);
    }

    /**
     * Get clients.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getClients(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->clients()->paginate($perPage);
    }

    /**
     * Get users with pending tasks.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsersWithPendingTasks(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->withPendingTasks()->paginate($perPage);
    }

    /**
     * Get users by age range.
     *
     * @param int $minAge
     * @param int $maxAge
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAgeRange(int $minAge, int $maxAge, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->byAgeRange($minAge, $maxAge)->paginate($perPage);
    }

    /**
     * Get statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $baseStats = parent::getStatistics();
        
        return array_merge($baseStats, [
            'active_users' => $this->repository->active()->count(),
            'inactive_users' => $this->repository->where('is_active', false)->count(),
            'email_verified' => $this->repository->emailVerified()->count(),
            'email_not_verified' => $this->repository->emailNotVerified()->count(),
            'project_managers' => $this->repository->projectManagers()->count(),
            'clients' => $this->repository->clients()->count(),
            'logged_in_today' => $this->repository->loggedInLastDays(1)->count(),
            'logged_in_this_week' => $this->repository->loggedInLastDays(7)->count(),
            'logged_in_this_month' => $this->repository->loggedInLastDays(30)->count(),
            'by_gender' => [
                'male' => $this->repository->byGender('male')->count(),
                'female' => $this->repository->byGender('female')->count(),
                'other' => $this->repository->byGender('other')->count(),
            ],
            'by_role' => [
                'admin' => $this->repository->byRole('admin')->count(),
                'manager' => $this->repository->byRole('manager')->count(),
                'user' => $this->repository->byRole('user')->count(),
            ],
        ]);
    }

    /**
     * Transform data for storage.
     *
     * @param array $data
     * @return array
     */
    protected function transformData(array $data): array
    {
        // Hash password if present
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        // Remove password confirmation
        unset($data['password_confirmation']);
        
        return $data;
    }

    /**
     * Transform model for response.
     *
     * @param User $model
     * @return array
     */
    public function transformModel(User $model): array
    {
        return array_merge($model->toArray(), [
            'full_name' => $model->full_name,
            'initials' => $model->initials,
            'avatar_url' => $model->avatar_url,
            'profile_completion_percentage' => $model->profile_completion_percentage,
            'roles' => $model->roles->pluck('name'),
            'permissions' => $model->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Get the repository instance.
     *
     * @return UserRepository
     */
    protected function getRepository(): UserRepository
    {
        return $this->repository;
    }
}
