<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// A collection of feature requests. Port of the Django `Board` model.
class Board extends Model
{
    protected $fillable = ['organization_id', 'name', 'slug', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // Fill the slug from the name on create, like Django's save() override.
    protected static function booted(): void
    {
        static::creating(function (Board $board) {
            if (blank($board->slug)) {
                $board->slug = Str::slug($board->name);
            }
        });
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
