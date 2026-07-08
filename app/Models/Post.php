<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A single feature request on a board. Port of the Django `Post` model.
class Post extends Model
{
    public const STATUSES = ['open', 'planned', 'in_progress', 'done', 'declined'];

    protected $fillable = ['board_id', 'author_id', 'title', 'body', 'status'];

    protected $attributes = ['status' => 'open'];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
