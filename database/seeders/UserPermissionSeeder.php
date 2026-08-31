<?php
namespace Database\Seeders;

use App\Helpers\UserPermissionHelper;
use App\Models\UserPermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            UserPermission::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='user_permissions'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            UserPermission::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            UserPermission::truncate();
        }

        foreach (UserPermissionHelper::modules() as $module) {
            foreach (UserPermissionHelper::modulesPermissions($module->id) as $modulePermission) {
                UserPermission::factory()->state([
                    'module' => $module->id,
                    'access' => $modulePermission->id,
                ])->create();
            }
        }

        $userPermissions = UserPermission::orderBy("id", "desc")->get();

        foreach ($userPermissions as $userPermission) {
            $userPermission->users()->detach();
        }

        $users = User::orderBy("id", "desc")->where("is_super_admin", false)->get();
        foreach ($users as $user) {
            $userPermissionIds = UserPermission::pluck("id");
            $user->userPermissions()->sync($userPermissionIds);
        }
    }
}
