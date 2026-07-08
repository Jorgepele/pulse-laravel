<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Links a user to an organization with a role. Port of the Django `Membership`.
class Membership extends Model
{
    public const ROLES = ['owner', 'admin', 'member'];

    protected $fillable = ['user_id', 'organization_id', 'role'];

    protected $attributes = ['role' => 'member'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
