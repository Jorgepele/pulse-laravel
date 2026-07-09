<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    // A user who owns and belongs to a personal organization.
    private function user(): User
    {
        $user = User::create(['name' => 'Jorge', 'email' => 'jorge@example.com', 'password' => 'secret123']);
        $org = Organization::create(['name' => 'Acme', 'owner_id' => $user->id]);
        $org->memberships()->create(['user_id' => $user->id, 'role' => 'owner']);

        return $user;
    }

    private function board(string $name = 'Feature Requests', bool $public = true): Board
    {
        $org = Organization::first() ?? Organization::create([
            'name' => 'Acme',
            'owner_id' => User::create(['name' => 'O', 'email' => 'o@example.com', 'password' => 'secret123'])->id,
        ]);

        return $org->boards()->create(['name' => $name, 'is_public' => $public]);
    }

    public function test_register_returns_a_token(): void
    {
        $res = $this->postJson('/api/register', ['email' => 'new@example.com', 'password' => 'secret123']);
        $res->assertCreated();
        $this->assertNotEmpty($res->json('token'));
    }

    public function test_register_creates_a_personal_organization(): void
    {
        $this->postJson('/api/register', ['email' => 'founder@example.com', 'password' => 'secret123'])
            ->assertCreated();

        $user = User::where('email', 'founder@example.com')->first();
        $org = $user->organizations()->first();
        $this->assertNotNull($org);
        $this->assertSame($user->id, $org->owner_id);
        $this->assertSame('owner', $user->memberships()->first()->role);
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        $this->user();
        $this->postJson('/api/login', ['email' => 'jorge@example.com', 'password' => 'nope'])
            ->assertStatus(422);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_boards_lists_only_public_boards(): void
    {
        $this->board('Feature Requests', true);
        $this->board('Internal', false);

        $slugs = collect($this->getJson('/api/boards')->assertOk()->json())->pluck('slug');
        $this->assertContains('feature-requests', $slugs);
        $this->assertNotContains('internal', $slugs);
    }

    public function test_creating_a_board_requires_auth(): void
    {
        $this->postJson('/api/boards', ['name' => 'Nope'])->assertUnauthorized();
    }

    public function test_creating_a_board_under_the_users_organization(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/boards', ['name' => 'Bugs']);
        $res->assertCreated()
            ->assertJsonPath('slug', 'bugs')
            ->assertJsonPath('organization_id', $user->organizations()->first()->id);
    }

    public function test_creating_a_post_requires_auth(): void
    {
        $board = $this->board();
        $this->postJson('/api/posts', ['board_id' => $board->id, 'title' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_creating_a_post_records_the_author(): void
    {
        Sanctum::actingAs($this->user());
        $board = $this->board();

        $res = $this->postJson('/api/posts', ['board_id' => $board->id, 'title' => 'Webhooks']);
        $res->assertCreated()
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('author', 'jorge@example.com');
    }

    public function test_posts_filter_by_status(): void
    {
        $user = $this->user();
        $board = $this->board();
        $board->posts()->create(['title' => 'Planned one', 'author_id' => $user->id, 'status' => 'planned']);
        $board->posts()->create(['title' => 'Open one', 'author_id' => $user->id, 'status' => 'open']);

        $titles = collect($this->getJson('/api/posts?status=planned')->assertOk()->json())->pluck('title');
        $this->assertContains('Planned one', $titles);
        $this->assertNotContains('Open one', $titles);
    }

    public function test_vote_toggles(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $post = $this->board()->posts()->create(['title' => 'X', 'author_id' => $user->id]);

        $this->postJson("/api/posts/{$post->id}/vote")
            ->assertOk()->assertJson(['voted' => true, 'vote_count' => 1]);
        $this->postJson("/api/posts/{$post->id}/vote")
            ->assertOk()->assertJson(['voted' => false, 'vote_count' => 0]);
    }

    public function test_comments_filter_and_bump_count(): void
    {
        $user = $this->user();
        $post = $this->board()->posts()->create(['title' => 'X', 'author_id' => $user->id]);
        $post->comments()->create(['body' => 'first', 'author_id' => $user->id]);

        $this->getJson("/api/comments?post={$post->id}")
            ->assertOk()->assertJsonCount(1);

        Sanctum::actingAs($user);
        $this->postJson('/api/comments', ['post_id' => $post->id, 'body' => 'second'])
            ->assertCreated()->assertJsonPath('author', 'jorge@example.com');

        $this->getJson("/api/posts/{$post->id}")->assertJsonPath('comment_count', 2);
    }
}
