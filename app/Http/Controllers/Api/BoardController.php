<?php

namespace App\Http\Controllers\Api;

use App\Models\Board;

class BoardController extends ApiController
{
    // GET /api/boards
    public function index()
    {
        $boards = Board::where('is_public', true)->orderBy('name')->get();

        return response()->json($boards->map(fn (Board $b) => $this->boardData($b)));
    }

    // GET /api/boards/{board}
    public function show(Board $board)
    {
        return response()->json($this->boardData($board));
    }
}
