<?php
namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Models\User;
use App\Models\UserPermission;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            User::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='users'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            User::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            User::truncate();
        }

        // Seeder
        $adminUser =User::factory()->state([
            'name'              => "Default Admin",
            'email'             => "admin@gmail.com",
            'email_verified_at' => now(),
            "is_default"        => true,
            "is_super_admin"    => true,
            "created_by_id"     => null,
            'gender'            => 'Male',
            'religion'          => 'Islam',
            'marital_status'    => 'Single',
        ])->create();

        for ($i = 0; $i < 25; $i++) {
            User::factory()->create();
        }

        $users = User::orderBy("id", "desc")->whereNot("id", $adminUser->id)->get();
        foreach ($users as $user) {
            $userPermissionIds = UserPermission::pluck("id");
            $user->userPermissions()->sync($userPermissionIds);
        }

        $profileImageUrl = MediaHelper::defaultAuthImage("1:1", "user");
        if ($profileImageUrl) {
            $users = User::orderBy("id", "desc")->get();
            foreach ($users as $user) {
                try {
                    $headers = get_headers($profileImageUrl, 1);
                    if (strpos($headers[0], '200') !== false) {
                        $profileImageExtension = pathinfo($profileImageUrl, PATHINFO_EXTENSION);
                        $profileImageExtension = in_array($profileImageExtension, ["png", "jpg", "jpeg"]) ? $profileImageExtension : "png";
                        $profileImageFileName  = MediaHelper::generateMediaName($user->name, $profileImageExtension, 200);
                        $user->addMediaFromUrl($profileImageUrl)
                            ->usingName($user->name)
                            ->usingFileName($profileImageFileName)
                            ->withCustomProperties(['caption' => $user->name, 'alt' => $user->name, "role" => MediaHelper::ROLE_PROFILE_IMAGE])
                            ->toMediaCollection($user->media_collection_name);
                    } else {
                        Log::info("Image not accessable user: {$user->name}");
                    }
                } catch (Exception $ex) {
                    Log::info("Failed to fetch Image for user {$user->name}: {$ex->getMessage()}");
                }
            }
        }
    }
}
