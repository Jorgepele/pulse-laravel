<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends ApiController
{
    // GET /api/comments?post=:id
    public function index(Request $request)
    {
        $comments = Comment::query()->oldest();
        if ($request->filled('post')) {
            $comments->where('post_id', $request->query('post'));
        }

        return response()->json($comments->get()->map(fn (Comment $c) => $this->commentData($c)));
    }

    // POST /api/comments  (auth required)
    public function store(Request $request)
    {
        $data = $request->validate([
            'post_id' => ['required', 'exists:posts,id'],
            'body' => ['required', 'string'],
        ]);
        $data['author_id'] = $request->user()->id;

        $comment = Comment::create($data);

        return response()->json($this->commentData($comment), 201);
    }
}
