<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Demo data for Pulse. Idempotent: re-running does not create duplicates.
    public function run(): void
    {
        $demo = User::firstOrCreate(
            ['email' => 'demo@pulse.dev'],
            ['name' => 'Demo', 'password' => 'demo12345']
        );
        $alice = User::firstOrCreate(
            ['email' => 'alice@pulse.dev'],
            ['name' => 'Alice', 'password' => 'demo12345']
        );

        $org = Organization::firstOrCreate(
            ['slug' => 'demo-workspace'],
            ['name' => 'Demo Workspace', 'owner_id' => $demo->id]
        );
        $org->memberships()->firstOrCreate(['user_id' => $demo->id], ['role' => 'owner']);

        $board = $org->boards()->firstOrCreate(['slug' => 'feature-requests'], ['name' => 'Feature Requests']);

        $posts = [
            ['title' => 'Dark mode', 'body' => 'Please add a dark theme.', 'status' => 'planned'],
            ['title' => 'Slack integration', 'body' => 'Notify a channel on new posts.', 'status' => 'open'],
            ['title' => 'CSV export', 'body' => 'Export all feedback to CSV.', 'status' => 'open'],
            ['title' => 'Mobile app', 'body' => 'A companion app.', 'status' => 'declined'],
        ];

        foreach ($posts as $attrs) {
            $post = $board->posts()->firstOrCreate(
                ['title' => $attrs['title']],
                ['body' => $attrs['body'], 'status' => $attrs['status'], 'author_id' => $demo->id]
            );

            $post->votes()->firstOrCreate(['user_id' => $demo->id]);
            if ($attrs['title'] === 'Dark mode') {
                $post->votes()->firstOrCreate(['user_id' => $alice->id]);
                $post->comments()->firstOrCreate(['body' => 'Yes please!'], ['author_id' => $alice->id]);
            }
        }

        $this->command->info('Seeded '.User::count().' users, '.Organization::count().' org, '.Post::count().' posts.');
    }
}
