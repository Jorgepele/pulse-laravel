<?php

namespace App\Http\Controllers\Api;

use App\Models\Board;
use Illuminate\Http\Request;

class BoardController extends ApiController
{
    // GET /api/boards
    public function index(Request $request)
    {
        $boards = $this->visibleBoards($request)->orderBy('name')->get();

        return response()->json($boards->map(fn (Board $b) => $this->boardData($b)));
    }

    // GET /api/boards/{board}
    public function show(Request $request, Board $board)
    {
        abort_unless($this->visibleBoards($request)->whereKey($board->id)->exists(), 404);

        return response()->json($this->boardData($board));
    }

    // POST /api/boards  (auth) — creates a board under the user's organization.
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $organization = $request->user()->organizations()->first();
        abort_unless($organization, 422, 'No organization');

        $board = $organization->boards()->create($data);

        return response()->json($this->boardData($board), 201);
    }
}
