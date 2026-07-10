<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends ApiController
{
    // GET /api/posts  (optionally ?board_id= and/or ?status=)
    public function index(Request $request)
    {
        $posts = $this->visiblePosts($request)->latest();
        if ($request->filled('board_id')) {
            $posts->where('board_id', $request->query('board_id'));
        }
        if ($request->filled('status')) {
            $posts->where('status', $request->query('status'));
        }

        return response()->json($posts->get()->map(fn (Post $p) => $this->postData($p)));
    }

    // GET /api/posts/{post}
    public function show(Request $request, Post $post)
    {
        abort_unless($this->visiblePosts($request)->whereKey($post->id)->exists(), 404);

        return response()->json($this->postData($post));
    }

    // POST /api/posts  (auth required) — only on a board the user can see.
    public function store(Request $request)
    {
        $data = $request->validate([
            'board_id' => ['required', 'exists:boards,id'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(Post::STATUSES)],
        ]);
        abort_unless($this->visibleBoards($request)->whereKey($data['board_id'])->exists(), 404);
        $data['author_id'] = $request->user()->id;

        $post = Post::create($data);

        return response()->json($this->postData($post), 201);
    }

    // POST /api/posts/{post}/vote  (auth required) — toggle the user's vote
    public function vote(Request $request, Post $post)
    {
        abort_unless($this->visiblePosts($request)->whereKey($post->id)->exists(), 404);

        $existing = $post->votes()->where('user_id', $request->user()->id)->first();

        if ($existing) {
            $existing->delete();
            $voted = false;
        } else {
            $post->votes()->create(['user_id' => $request->user()->id]);
            $voted = true;
        }

        return response()->json(['voted' => $voted, 'vote_count' => $post->votes()->count()]);
    }
}
