<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// A collection of feature requests. Port of the Django `Board` model.
class Board extends Model
{
    protected $fillable = ['organization_id', 'name', 'slug', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    // Tenant visibility, mirroring Django's `BoardQuerySet.visible_to`: any public
    // board, plus the private boards of the organizations the user belongs to.
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->where('is_public', true);
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('is_public', true)
                ->orWhereHas('organization.memberships',
                    fn (Builder $m) => $m->where('user_id', $user->id));
        });
    }

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
