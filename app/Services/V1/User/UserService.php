<?php
namespace App\Services\V1\User;

use App\Models\User;
use App\Helpers\FileUploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function updateProfile(User $user, array $data, Request $request)
    {
        DB::beginTransaction();
        try {
            // Remove empty values to avoid overwriting with null/empty strings
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });

            // Handle email uniqueness check (already handled by validation, but double-check)
            if (isset($data['email']) && $data['email'] !== $user->email) {
                if (User::where('email', $data['email'])
                    ->where('id', '!=', $user->id)
                    ->exists()) {
                    return ['error' => 'Email already exists', 'code' => 422];
                }
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                try {
                    // Delete old avatar if exists
                    if ($user->avatar) {
                        FileUploadHelper::delete($user->avatar);
                    }
                    
                    // Upload new avatar
                    $data['avatar'] = FileUploadHelper::upload($request->file('avatar'), 'avatars');
                } catch (\Exception $e) {
                    return ['error' => 'Failed to upload avatar: ' . $e->getMessage(), 'code' => 500];
                }
            }

            // Update user with filtered data
            $user->update($data);

            DB::commit();
            $user->refresh();

            return ['user' => $user];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => 'Failed to update profile: ' . $e->getMessage(), 'code' => 500];
        }
    }

    public function deleteProfile($user)
    {
        DB::beginTransaction();
        try {
            $deletedUser = $user->replicate();

            if ($user->avatar) {
                FileUploadHelper::delete($user->avatar);
            }

            $user->delete();
            DB::commit();

            return ['user' => $deletedUser];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => $e->getMessage(), 'code' => 500];
        }
    }
}