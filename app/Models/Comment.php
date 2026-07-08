<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A comment on a post. Port of the Django `Comment` model.
class Comment extends Model
{
    protected $fillable = ['post_id', 'author_id', 'body'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
