<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create(['name' => 'Jorge', 'email' => 'jorge@example.com', 'password' => 'secret123']);
    }

    public function test_register_returns_a_token(): void
    {
        $res = $this->postJson('/api/register', ['email' => 'new@example.com', 'password' => 'secret123']);
        $res->assertCreated();
        $this->assertNotEmpty($res->json('token'));
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
        Board::create(['name' => 'Feature Requests']);
        Board::create(['name' => 'Internal', 'is_public' => false]);

        $slugs = collect($this->getJson('/api/boards')->assertOk()->json())->pluck('slug');
        $this->assertContains('feature-requests', $slugs);
        $this->assertNotContains('internal', $slugs);
    }

    public function test_creating_a_post_requires_auth(): void
    {
        $board = Board::create(['name' => 'Feature Requests']);
        $this->postJson('/api/posts', ['board_id' => $board->id, 'title' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_creating_a_post_records_the_author(): void
    {
        Sanctum::actingAs($this->user());
        $board = Board::create(['name' => 'Feature Requests']);

        $res = $this->postJson('/api/posts', ['board_id' => $board->id, 'title' => 'Webhooks']);
        $res->assertCreated()
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('author', 'jorge@example.com');
    }

    public function test_vote_toggles(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $board = Board::create(['name' => 'B']);
        $post = $board->posts()->create(['title' => 'X', 'author_id' => $user->id]);

        $this->postJson("/api/posts/{$post->id}/vote")
            ->assertOk()->assertJson(['voted' => true, 'vote_count' => 1]);
        $this->postJson("/api/posts/{$post->id}/vote")
            ->assertOk()->assertJson(['voted' => false, 'vote_count' => 0]);
    }

    public function test_comments_filter_and_bump_count(): void
    {
        $user = $this->user();
        $board = Board::create(['name' => 'B']);
        $post = $board->posts()->create(['title' => 'X', 'author_id' => $user->id]);
        $post->comments()->create(['body' => 'first', 'author_id' => $user->id]);

        $this->getJson("/api/comments?post={$post->id}")
            ->assertOk()->assertJsonCount(1);

        Sanctum::actingAs($user);
        $this->postJson('/api/comments', ['post_id' => $post->id, 'body' => 'second'])
            ->assertCreated()->assertJsonPath('author', 'jorge@example.com');

        $this->getJson("/api/posts/{$post->id}")->assertJsonPath('comment_count', 2);
    }
}
