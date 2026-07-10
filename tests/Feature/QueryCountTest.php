<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Organization;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Listing posts must cost the same number of queries whatever the page holds.
// Guards against the N+1 problem: before `with` + `withCount`, every post
// triggered its own queries for the author, the votes and the comments.
class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create(['name' => 'Jorge', 'email' => 'jorge@example.com', 'password' => 'secret123']);
        $org = Organization::create(['name' => 'Acme', 'owner_id' => $this->user->id]);
        $org->memberships()->create(['user_id' => $this->user->id, 'role' => 'owner']);
        $this->board = $org->boards()->create(['name' => 'Feature Requests', 'is_public' => true]);
    }

    public function test_listing_posts_does_not_scale_with_the_number_of_posts(): void
    {
        $this->assertSame($this->queriesListingPosts(1), $this->queriesListingPosts(5));
    }

    private function queriesListingPosts(int $count): int
    {
        Post::query()->delete();
        for ($n = 0; $n < $count; $n++) {
            $post = $this->board->posts()->create(['title' => "Post {$n}", 'author_id' => $this->user->id]);
            $post->votes()->create(['user_id' => $this->user->id]);
            $post->comments()->create(['body' => 'A comment', 'author_id' => $this->user->id]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/posts')->assertOk()->assertJsonCount($count);
        $queries = count(DB::getRawQueryLog());
        DB::disableQueryLog();

        return $queries;
    }
}
