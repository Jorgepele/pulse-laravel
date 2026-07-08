<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

// Public authentication endpoints.
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public reads.
Route::get('/boards', [BoardController::class, 'index']);
Route::get('/boards/{board}', [BoardController::class, 'show']);
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/comments', [CommentController::class, 'index']);

// Writes require a Sanctum token (Authorization: Bearer <token>).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/boards', [BoardController::class, 'store']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::post('/posts/{post}/vote', [PostController::class, 'vote']);
    Route::post('/comments', [CommentController::class, 'store']);
});
