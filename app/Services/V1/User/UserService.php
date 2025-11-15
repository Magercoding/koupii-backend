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
            if (
                isset($data['email']) &&
                User::where('email', $data['email'])
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                return ['error' => 'Email already exists', 'code' => 422];
            }

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    FileUploadHelper::delete($user->avatar);
                }
                $data['avatar'] = FileUploadHelper::upload($request->file('avatar'), 'avatar');
            }

            $user->update($data);

            DB::commit();
            $user->refresh();

            return ['user' => $user];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => $e->getMessage(), 'code' => 500];
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