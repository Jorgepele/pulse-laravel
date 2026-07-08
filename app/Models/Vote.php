<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// An upvote on a post. Port of the Django `Vote` model: one per (post, user).
class Vote extends Model
{
    protected $fillable = ['post_id', 'user_id'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
