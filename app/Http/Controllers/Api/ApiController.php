<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

// Base for the JSON API. Small hand-written serializers keep the JSON shape
// explicit and matching the Django/Rails versions of Pulse.
abstract class ApiController extends Controller
{
    protected function boardData(Board $board): array
    {
        return [
            'id' => $board->id,
            'name' => $board->name,
            'slug' => $board->slug,
            'is_public' => $board->is_public,
            'organization_id' => $board->organization_id,
        ];
    }

    protected function postData(Post $post): array
    {
        return [
            'id' => $post->id,
            'board_id' => $post->board_id,
            'title' => $post->title,
            'body' => $post->body,
            'status' => $post->status,
            'vote_count' => $post->votes()->count(),
            'comment_count' => $post->comments()->count(),
            'author' => $post->author?->email,
            'created_at' => $post->created_at,
        ];
    }

    protected function commentData(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'body' => $comment->body,
            'author' => $comment->author?->email,
            'created_at' => $comment->created_at,
        ];
    }

    protected function userData(User $user, ?string $token = null): array
    {
        return array_filter([
            'id' => $user->id,
            'email' => $user->email,
            'token' => $token,
        ], fn ($v) => $v !== null);
    }
}
