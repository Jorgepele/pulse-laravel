<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends ApiController
{
    // GET /api/posts  (optionally ?board_id=)
    public function index(Request $request)
    {
        $posts = Post::query()->latest();
        if ($request->filled('board_id')) {
            $posts->where('board_id', $request->query('board_id'));
        }

        return response()->json($posts->get()->map(fn (Post $p) => $this->postData($p)));
    }

    // GET /api/posts/{post}
    public function show(Post $post)
    {
        return response()->json($this->postData($post));
    }

    // POST /api/posts  (auth required)
    public function store(Request $request)
    {
        $data = $request->validate([
            'board_id' => ['required', 'exists:boards,id'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(Post::STATUSES)],
        ]);
        $data['author_id'] = $request->user()->id;

        $post = Post::create($data);

        return response()->json($this->postData($post), 201);
    }

    // POST /api/posts/{post}/vote  (auth required) — toggle the user's vote
    public function vote(Request $request, Post $post)
    {
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
