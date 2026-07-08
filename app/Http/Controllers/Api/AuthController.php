<?php

namespace App\Http\Controllers\Api;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    // POST /api/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Create the user together with a personal organization they own, so
        // every account has a tenant to create boards under (multi-tenant core).
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['email'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed by the model cast
            ]);

            $org = Organization::create([
                'name' => $data['email']."'s workspace",
                'owner_id' => $user->id,
            ]);
            $org->memberships()->create(['user_id' => $user->id, 'role' => 'owner']);

            return $user;
        });

        $token = $user->createToken('api')->plainTextToken;

        return response()->json($this->userData($user, $token), 201);
    }

    // POST /api/login
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json($this->userData($user, $token));
    }

    // GET /api/me
    public function me(Request $request)
    {
        return response()->json($this->userData($request->user()));
    }
}
