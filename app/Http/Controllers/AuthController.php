<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Resources\UserResource;

class AuthController extends Controller {
    private function jwt(User $user) {
        $signer = new Sha256();
        $token = (new Builder())->setIssuer(env('APP_URL'))
            ->setAudience(env('APP_URL'))
            ->setId(uniqid(), true)
            ->setIssuedAt(Carbon::now()->getTimestamp())
            ->setNotBefore(Carbon::now()->getTimestamp())
            ->setExpiration(Carbon::now()->getTimestamp() + env('JWT_EXPIRE'))
            ->set('user_id', $user->id)
            ->sign($signer, env('APP_KEY'))
            ->getToken();

        return $token;
    }

    public function authenticate(Request $request) {
        $this->validate($request, [
            'id'       => ['required', 'string'],
            'password' => ['required', 'string']
        ]);

        $user = User::find($request->input('id'));

        if (!$user)
            throw new HttpException(401);

        if (Hash::check($request->input('password'), $user->password))
            return ['token' => $this->jwt($user)->__toString()];
        else throw new HttpException(401);
    }

    public function userInfo(Request $request) {
        return response(new UserResource($request->user()));
    }

    public function changePassword(Request $request) {
        $this->validate($request, [
            'password' => ['required', 'string', 'min:8']
        ]);
        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);
        return response('', 204);
    }
}
