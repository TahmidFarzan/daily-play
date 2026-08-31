<?php

namespace Tests\Feature;

use App\Helpers\UserPermissionHelper;
use App\Models\GameDifficulty;
use App\Models\User;
use App\Models\UserPermission;
use App\Policies\GameDifficultyPolicy;
use Database\Seeders\GameDifficultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class GameDifficultyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->state([
            'name' => 'Default Admin',
            'is_super_admin' => true,
            'email_verified_at' => now(),
            'created_by_id' => null,
        ])->create();

        $this->seed(GameDifficultySeeder::class);
    }

    protected function createPermissionUser(bool $superAdmin = false): User
    {
        $user = User::factory()->state([
            'is_super_admin' => $superAdmin,
            'email_verified_at' => now(),
        ])->create();

        if (! $superAdmin) {
            $permission = UserPermission::create([
                'module' => UserPermissionHelper::MODULE_GAME_DIFFICULTY,
                'access' => UserPermissionHelper::ACCESS_VIEW_ANY,
            ]);

            $permission2 = UserPermission::create([
                'module' => UserPermissionHelper::MODULE_GAME_DIFFICULTY,
                'access' => UserPermissionHelper::ACCESS_VIEW,
            ]);

            $user->userPermissions()->sync([$permission->id, $permission2->id]);
        }

        return $user;
    }

    public function test_easy_takes_normal_and_hard_are_seeded(): void
    {
        $this->assertDatabaseHas('game_difficulties', ['slug' => 'easy']);
        $this->assertDatabaseHas('game_difficulties', ['slug' => 'normal']);
        $this->assertDatabaseHas('game_difficulties', ['slug' => 'hard']);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(GameDifficultySeeder::class);

        $this->assertEquals(3, GameDifficulty::count());
    }

    public function test_created_by_relationship_works(): void
    {
        $admin = User::factory()->state(['is_super_admin' => true])->create();

        $difficulty = GameDifficulty::create([
            'name' => 'Easy',
            'brief' => 'Test brief',
            'created_by_id' => $admin->id,
        ]);

        $this->assertNotNull($difficulty->fresh()->createdBy);
        $this->assertEquals($admin->id, $difficulty->fresh()->createdBy->id);
    }

    public function test_authorized_user_can_view_difficulty_index(): void
    {
        $user = $this->createPermissionUser();
        $this->actingAs($user);

        $response = $this->get(route('back-office.game-difficulties.index'));

        $response->assertSuccessful();
    }

    public function test_authorized_user_can_view_difficulty_details(): void
    {
        $user = $this->createPermissionUser();
        $this->actingAs($user);

        $response = $this->get(route('back-office.game-difficulties.details', 'easy'));

        $response->assertSuccessful();
    }

    public function test_super_admin_user_can_view_difficulty(): void
    {
        $user = $this->createPermissionUser(true);
        $this->actingAs($user);

        $response = $this->get(route('back-office.game-difficulties.index'));

        $response->assertSuccessful();
    }

    public function test_user_without_permission_cannot_view_difficulty(): void
    {
        $user = User::factory()->state([
            'email_verified_at' => now(),
        ])->create();
        $this->actingAs($user);

        $response = $this->get(route('back-office.game-difficulties.index'));

        $response->assertForbidden();
    }

    public function test_save_route_does_not_exist(): void
    {
        $route = app('router')->getRoutes()->getByName('back-office.game-difficulties.save');

        $this->assertNull($route);
    }

    public function test_post_to_save_returns_404(): void
    {
        $user = $this->createPermissionUser();
        $this->actingAs($user);

        $response = $this->post('/back-office/game-difficulties/save', [
            'slug' => 'easy',
            'brief' => 'Updated easy brief',
            'config' => ['zip' => ['time_limit' => 200]],
        ]);

        $response->assertNotFound();
    }

    public function test_update_is_not_allowed(): void
    {
        $user = $this->createPermissionUser();

        $this->assertFalse(Gate::forUser($user)->allows('update', GameDifficulty::where('slug', 'easy')->firstOrFail()));
    }

    public function test_create_is_not_allowed(): void
    {
        $user = $this->createPermissionUser();
        $this->actingAs($user);

        $policy = new GameDifficultyPolicy;
        $difficulty = new GameDifficulty;

        $this->assertTrue($policy->create($user)->denied());
    }

    public function test_delete_is_not_allowed(): void
    {
        $user = $this->createPermissionUser();
        $this->actingAs($user);

        $policy = new GameDifficultyPolicy;
        $difficulty = GameDifficulty::firstOrFail();

        $this->assertTrue($policy->delete($user, $difficulty)->denied());
    }
}
