<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function createPlayer(array $attributes = []): Player
    {
        return Player::create(array_merge([
            'name' => 'Tahmid Farzan',
            'email' => 'tahmid@example.com',
            'mobile' => '01700000000',
            'address' => 'Dhaka',
        ], $attributes));
    }

    public function test_get_retrieves_player_by_slug(): void
    {
        $player = $this->createPlayer();

        $response = $this->getJson(route('players.get', ['slug' => $player->slug]));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $player->id)
            ->assertJsonPath('data.slug', $player->slug)
            ->assertJsonPath('data.name', $player->name)
            ->assertJsonPath('data.email', $player->email)
            ->assertJsonPath('data.mobile', $player->mobile)
            ->assertJsonPath('data.address', $player->address);
    }

    public function test_get_retrieves_player_by_email(): void
    {
        $player = $this->createPlayer();

        $this->getJson(route('players.get', ['slug' => $player->email]))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $player->id);
    }

    public function test_get_retrieves_player_by_mobile(): void
    {
        $player = $this->createPlayer();

        $this->getJson(route('players.get', ['slug' => $player->mobile]))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $player->id);
    }

    public function test_get_with_invalid_identifier_returns_not_found(): void
    {
        $this->getJson(route('players.get', ['slug' => 'unknown-identifier']))
            ->assertNotFound()
            ->assertJsonPath('status', 'error');
    }

    public function test_get_response_has_no_session_id(): void
    {
        $player = $this->createPlayer();

        $response = $this->getJson(route('players.get', ['slug' => $player->slug]));

        $response->assertOk();
        $this->assertArrayNotHasKey('session_id', $response->json('data'));
    }

    public function test_save_requires_name(): void
    {
        $this->postJson(route('players.save'), ['email' => 'new@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_save_rejects_when_email_and_mobile_are_missing(): void
    {
        $this->postJson(route('players.save'), ['name' => 'John Doe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonValidationErrors('mobile');
    }

    public function test_save_accepts_email_only(): void
    {
        $response = $this->postJson(route('players.save'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame(1, Player::count());
    }

    public function test_save_accepts_mobile_only(): void
    {
        $response = $this->postJson(route('players.save'), [
            'name' => 'John Doe',
            'mobile' => '01800000000',
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame(1, Player::count());
    }

    public function test_save_accepts_email_and_mobile(): void
    {
        $response = $this->postJson(route('players.save'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '01800000000',
            'address' => 'Chittagong',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.mobile', '01800000000')
            ->assertJsonPath('data.address', 'Chittagong');
    }

    public function test_save_creates_player_with_backend_generated_slug(): void
    {
        $response = $this->postJson(route('players.save'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $response->assertOk();

        $player = Player::firstOrFail();

        $this->assertNotEmpty($player->slug);
        $this->assertSame($player->slug, $response->json('data.slug'));
        $this->assertNotNull($response->json('data.id'));
    }

    public function test_save_updates_existing_player_by_email_without_duplicate(): void
    {
        $player = $this->createPlayer(['email' => 'shared@example.com']);

        $response = $this->postJson(route('players.save'), [
            'name' => 'Renamed Player',
            'email' => 'shared@example.com',
        ]);

        $response->assertOk();

        $this->assertSame(1, Player::count());
        $this->assertSame($player->id, $response->json('data.id'));
        $this->assertSame('Renamed Player', $response->json('data.name'));
    }

    public function test_save_updates_existing_player_by_mobile_without_duplicate(): void
    {
        $player = $this->createPlayer(['mobile' => '01900000000']);

        $response = $this->postJson(route('players.save'), [
            'name' => 'Renamed Player',
            'mobile' => '01900000000',
        ]);

        $response->assertOk();

        $this->assertSame(1, Player::count());
        $this->assertSame($player->id, $response->json('data.id'));
    }

    public function test_save_identifies_existing_player_by_slug(): void
    {
        $player = $this->createPlayer();

        $response = $this->postJson(route('players.save'), [
            'slug' => $player->slug,
            'name' => 'Slug Updated Name',
            'email' => $player->email,
        ]);

        $response->assertOk();

        $this->assertSame(1, Player::count());
        $this->assertSame($player->id, $response->json('data.id'));
        $this->assertSame('Slug Updated Name', $response->json('data.name'));
        $this->assertSame($player->slug, $response->json('data.slug'));
    }

    public function test_save_response_has_no_session_id(): void
    {
        $response = $this->postJson(route('players.save'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertOk();
        $this->assertArrayNotHasKey('session_id', $response->json('data'));
    }

    public function test_players_table_and_model_have_no_session_id(): void
    {
        $this->assertFalse(Schema::hasColumn('players', 'session_id'));
        $this->assertNotContains('session_id', (new Player())->getFillable());
    }
}