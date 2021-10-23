<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use App\Resources\UserResource;

class AuthController extends Controller {
    private function jwt(User $user) {
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(env('APP_KEY')));
        $now = new \DateTimeImmutable();
        $token = $config->builder()
            ->issuedBy(env('APP_URL'))
            ->permittedFor(env('APP_URL'))
            ->identifiedBy(uniqid())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+'.env('JWT_EXPIRE')))
            ->withClaim('user_id', $user->id)
            ->withClaim('session_key', $user->session_key)
            ->getToken($config->signer(), $config->signingKey());

        return $token;
    }

    public function authenticate(Request $request) {
        $this->validate($request, [
            'id'       => ['required', 'string'],
            'password' => ['required', 'string']
        ]);

        $user = User::find($request->input('id'));

        if (!$user)
            abort(401);

        if (Hash::check($request->input('password'), $user->password))
            return ['token' => $this->jwt($user)->toString()];
        else abort(401);
    }

    public function currentUserInfo(Request $request) {
        return response(new UserResource($request->user()));
    }

    public function all() {
        return response()->json(UserResource::collection(User::all()));
    }

    public function show(Request $request, $id) {
        if (!$request->user()->hasPermission("admin") && $id !== $request->user()->id)
            abort(403);

        return response()->json(new UserResource(User::findOrFail($id)));
    }

    public function changePassword(Request $request, $id) {
        if (!$request->user()->hasPermission("admin") && $id !== $request->user()->id)
            abort(403);

        $this->validate($request, [
            'password' => ['required', 'string', 'min:8']
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);
        return response('', 204);
    }

    public function regenerate($id) {
        $user = User::findOrFail($id);

        do {
            $key = Str::random(10);
        } while ($user->session_key === $key);

        $user->update([
            'session_key' => $key
        ]);
        return response('', 204);
    }
}
