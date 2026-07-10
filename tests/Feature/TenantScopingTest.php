<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// A private board, and everything hanging off it, must never leak outside the
// organization that owns it.
class TenantScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $outsider;

    private Board $privateBoard;

    private Board $publicBoard;

    private Post $secretPost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = $this->userWithOrg('member@acme.com', 'Acme');
        $acme = $this->member->organizations()->first();
        $this->privateBoard = $acme->boards()->create(['name' => 'Internal', 'is_public' => false]);
        $this->publicBoard = $acme->boards()->create(['name' => 'Feature Requests', 'is_public' => true]);
        $this->secretPost = $this->privateBoard->posts()->create([
            'title' => 'Secret roadmap item', 'author_id' => $this->member->id,
        ]);

        $this->outsider = $this->userWithOrg('out@globex.com', 'Globex');
    }

    private function userWithOrg(string $email, string $orgName): User
    {
        $user = User::create(['name' => $orgName, 'email' => $email, 'password' => 'secret123']);
        $org = Organization::create(['name' => $orgName, 'owner_id' => $user->id]);
        $org->memberships()->create(['user_id' => $user->id, 'role' => 'owner']);

        return $user;
    }

    public function test_member_sees_own_private_board(): void
    {
        Sanctum::actingAs($this->member);
        $slugs = collect($this->getJson('/api/boards')->assertOk()->json())->pluck('slug');
        $this->assertContains('internal', $slugs);
    }

    public function test_outsider_does_not_see_the_private_board(): void
    {
        Sanctum::actingAs($this->outsider);
        $slugs = collect($this->getJson('/api/boards')->assertOk()->json())->pluck('slug');
        $this->assertNotContains('internal', $slugs);
    }

    public function test_outsider_cannot_show_the_private_board(): void
    {
        Sanctum::actingAs($this->outsider);
        $this->getJson("/api/boards/{$this->privateBoard->id}")->assertNotFound();
    }

    public function test_anonymous_does_not_see_posts_of_the_private_board(): void
    {
        $titles = collect($this->getJson('/api/posts')->assertOk()->json())->pluck('title');
        $this->assertNotContains('Secret roadmap item', $titles);
    }

    public function test_member_sees_posts_of_the_private_board(): void
    {
        Sanctum::actingAs($this->member);
        $titles = collect($this->getJson('/api/posts')->assertOk()->json())->pluck('title');
        $this->assertContains('Secret roadmap item', $titles);
    }

    public function test_outsider_cannot_show_a_private_post(): void
    {
        Sanctum::actingAs($this->outsider);
        $this->getJson("/api/posts/{$this->secretPost->id}")->assertNotFound();
    }

    public function test_outsider_cannot_vote_on_a_private_post(): void
    {
        Sanctum::actingAs($this->outsider);
        $this->postJson("/api/posts/{$this->secretPost->id}/vote")->assertNotFound();
    }

    public function test_outsider_cannot_post_on_the_private_board(): void
    {
        Sanctum::actingAs($this->outsider);
        $this->postJson('/api/posts', ['board_id' => $this->privateBoard->id, 'title' => 'Intruder'])
            ->assertNotFound();
        $this->assertSame(1, Post::count());
    }

    public function test_outsider_cannot_comment_on_a_private_post(): void
    {
        Sanctum::actingAs($this->outsider);
        $this->postJson('/api/comments', ['post_id' => $this->secretPost->id, 'body' => 'Peeking'])
            ->assertNotFound();
        $this->assertSame(0, Comment::count());
    }

    public function test_outsider_does_not_see_comments_on_a_private_post(): void
    {
        $this->secretPost->comments()->create(['body' => 'Internal note', 'author_id' => $this->member->id]);

        Sanctum::actingAs($this->outsider);
        $bodies = collect($this->getJson('/api/comments')->assertOk()->json())->pluck('body');
        $this->assertNotContains('Internal note', $bodies);
    }

    public function test_anyone_authenticated_can_post_on_a_public_board(): void
    {
        Sanctum::actingAs($this->outsider);
        $this->postJson('/api/posts', ['board_id' => $this->publicBoard->id, 'title' => 'Please add dark mode'])
            ->assertCreated();
    }
}
