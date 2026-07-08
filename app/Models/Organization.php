<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// A tenant. Every board (and its posts) belongs to one organization.
// Port of the Django `Organization` model.
class Organization extends Model
{
    protected $fillable = ['name', 'slug', 'owner_id'];

    protected static function booted(): void
    {
        static::creating(function (Organization $org) {
            if (blank($org->slug)) {
                $org->slug = Str::slug($org->name);
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'memberships');
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }
}
