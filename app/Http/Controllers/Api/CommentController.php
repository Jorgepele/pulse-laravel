<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends ApiController
{
    // GET /api/comments?post=:id
    public function index(Request $request)
    {
        // Comments inherit the visibility of their post's board.
        $comments = Comment::whereIn('post_id', $this->visiblePosts($request)->select('id'))->oldest();
        if ($request->filled('post')) {
            $comments->where('post_id', $request->query('post'));
        }

        return response()->json($comments->get()->map(fn (Comment $c) => $this->commentData($c)));
    }

    // POST /api/comments  (auth required) — only on a post the user can see.
    public function store(Request $request)
    {
        $data = $request->validate([
            'post_id' => ['required', 'exists:posts,id'],
            'body' => ['required', 'string'],
        ]);
        abort_unless($this->visiblePosts($request)->whereKey($data['post_id'])->exists(), 404);
        $data['author_id'] = $request->user()->id;

        $comment = Comment::create($data);

        return response()->json($this->commentData($comment), 201);
    }
}
