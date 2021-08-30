<?php
namespace Tests;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use \Carbon\Carbon;
use \Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Void_;

class AuthJwtTest extends TestCase {
    private static function getToken(TestCase $tc, $perms = []) {
        $password = Str::random(16);

        $data = [
            'password'=>Hash::make($password)
        ];
        foreach ($perms as $val) $data['perm_' . $val] = true;
        $user = User::factory()->create($data);
        $id = $user->id;
        $response = $tc->json(
            'POST',
            '/auth/login',
            ['id'=>$id, 'password'=>$password]
        );
        $response->assertResponseOk();
        $response->seeJsonStructure(['token']);

        $jwc_token = json_decode($response->response->getContent())->token;

        return [
            'user' => $user,
            'password' => $password,
            'auth_hdr' => ['Authorization' => "bearer {$jwc_token}"],
        ];
    }

    // ======== login & auth (/auth/me) ========

    /**
     * /auth/login allow only post
     * @return void
     */
    public function testLoginGetNotAllowed() {
        $response = $this->get('/auth/login');
        $response->assertResponseStatus(405);
    }

    /**
     * /auth/me allow only get
     * @return void
     */
    public function testUserPostNotAllowed() {
        $response = $this->json('POST', '/auth/me');
        $response->assertResponseStatus(405);
    }

    /**
     * logging in with not existing user will be failed
     *
     * @return void
     */
    public function testUserNotFound() {
        $response = $this->json('POST', '/auth/login', [
            'id'=>Str::random(16),
            'password'=>Str::random(16)
        ]);
        $response->assertResponseStatus(401);
    }

    /**
     * logging in with wring password will be failed
     *
     * @return void
     */
    public function testPasswordWrong() {
        $user = $this->getToken($this);
        $response = $this->json('POST', '/auth/login', [
            'id'=>$user['user']->id,
            'password'=>Str::random(16)
        ]);
        $response->assertResponseStatus(401);
    }

    /**
     * login returns jwc token
     *
     * @return void
     */
    public function testLoginSuccessful() {
        // login and get token
        $user = $this->getToken($this);

        $response = $this->json('POST', '/auth/login', [
            'id'=>$user['user']->id,
            'password'=>$user['password']
        ]);
        $response->assertResponseOk();
        $response->seeJsonStructure(['token']);

        $jwc_token = json_decode($response->response->getContent())->token;

        $response = $this->get('/auth/me', ['Authorization'=>'bearer '.$jwc_token]);
        $response->assertResponseOk();
        $response->seeJson([
            'id'=>$user['user']->id,
            'name'=>$user['user']->name
        ]);
    }

    /**
     * user info permission bits
     * @return void
     */
    public function testPermissionObj() {
        $perms = [];
        foreach ([
            'admin',
            'reservation',
            'executive',
            'exhibition',
        ] as $name) {
            if (rand(0, 1) === 1) $perms[] = $name;
        }

        $user = $this->getToken($this, $perms);
        $response = $this->get('/auth/me', $user['auth_hdr']);
        $response->assertResponseOk();
        $response->seeJsonEquals([
            'id' => $user['user']->id,
            'name' => $user['user']->name,
            'permissions' => [
                'admin' => $user['user']->perm_admin,
                'reservation' => $user['user']->perm_reservation,
                'executive' => $user['user']->perm_executive,
                'exhibition' => $user['user']->perm_exhibition,
                'teacher' => $user['user']->perm_teacher,
            ],
        ]);
    }

    /**
     * user info denies access without token
     * @return void
     */
    public function testNoToken() {
        $response = $this->get('/auth/me');
        $response->assertResponseStatus(401);

        $response = $this->get('/auth/me', ['Authorization'=>'bearer invalid_token']);
        $response->assertResponseStatus(401);
    }

    /**
     * user info denies access with expired token
     * @return void
     */
    public function testExpiredToken() {
        // login and get token
        $user = $this->getToken($this);
        CarbonImmutable::setTestNow((new \DateTimeImmutable())->modify(env('JWT_EXPIRE'))->modify('+1 seconds'));
        // now token must be expired

        $response = $this->get('/auth/me', $user['auth_hdr']);
        $response->assertResponseStatus(401);
        CarbonImmutable::setTestNow();
    }

    // ======== users ========

    /**
     * all users info
     * @return void
     */
    public function testAllUsers() {
        $count = 5;
        User::factory()->count($count - 1)->create();
        $admin_user = User::factory()->create([ 'perm_admin' => 1 ]);

        $this->actingAs($admin_user)->get('/auth/users');
        $this->assertResponseOk();
        $data = $this->response->json();
        $this->assertCount($count, $data);
    }

    /**
     * general user cannot get all users info
     * @return void
     */
    public function testAllUsersByGeneralUser() {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/auth/users');
        $this->assertResponseStatus(403);
    }

    /**
     * general user can get their own user info
     * @return void
     */
    public function testOwnUserInfo() {
        $user = User::factory()->create();
        $id = $user->id;

        $this->actingAs($user)->get("/auth/users/$id");
        $this->assertResponseOk();
        $this->seeJsonEquals([
            'id' => $user->id,
            'name' => $user->name,
            'permissions' => [
                'admin' => $user->perm_admin,
                'reservation' => $user->perm_reservation,
                'executive' => $user->perm_executive,
                'exhibition' => $user->perm_exhibition,
                'teacher' => $user->perm_teacher,
            ],
        ]);
    }

    /**
     * admin user can get other user info
     * @return void
     */
    public function testOtherUserInfo() {
        $user = User::factory()->create();
        $id = $user->id;
        $admin_user=User::factory()->create([
            'perm_admin' => 1
        ]);

        $this->actingAs($admin_user)->get("/auth/users/$id");
        $this->assertResponseOk();
        $this->seeJsonEquals([
            'id' => $user->id,
            'name' => $user->name,
            'permissions' => [
                'admin' => $user->perm_admin,
                'reservation' => $user->perm_reservation,
                'executive' => $user->perm_executive,
                'exhibition' => $user->perm_exhibition,
                'teacher' => $user->perm_teacher,
            ],
        ]);
    }

    /**
     * general user cannot get other user info
     * @return void
     */
    public function testOtherUserInfoByGeneralUser() {
        $users = User::factory()->count(2)->create();
        $id = $users[0]->id;

        $this->actingAs($users[1])->get("/auth/users/$id");
        $this->assertResponseStatus(403);
    }

    /**
     * cannot get user info about non-existing user
     * @return void
     */
    public function testNonExistingUser() {
        $admin_user = User::factory()->create([ 'perm_admin'=> 1 ]);
        $id = Str::random(16);

        $this->actingAs($admin_user)->get("/auth/users/$id");
        $this->assertResponseStatus(404);
    }

    // ======== change password =========

    /**
     * change password without login must be failed
     * @return void
     */
    public function testChangePasswordAnonymously() {
        $user = User::factory()->create([
            'password'=>Hash::make(Str::random(16))
        ]);
        $id = $user->id;

        $new_password = Str::random(16);
        $response = $this->json(
            'POST',
            "/auth/users/$id/change_password",
            ['password'=>$new_password]
        );
        $response->assertResponseStatus(401);
    }

    /**
     * password less than 8 chars must be rejected
     * @return void
     */
    public function testWeakNewPassword() {
        // create user
        $old_password = Str::random(16); // initial does not matter
        $new_weak_password = Str::random(7); // < 8
        $new_strong_password = Str::random(8); // >= 8

        $user = User::factory()->create([
            'password'=>Hash::make($old_password)
        ]);
        $id = $user->id;

        // login first
        $response = $this->json(
            'POST',
            '/auth/login',
            ['id'=>$id, 'password'=>$old_password]
        );
        $response->assertResponseOk();
        $response->seeJsonStructure(['token']);

        $jwc_token = json_decode($response->response->getContent())->token;

        // weak password must be rejected
        $response = $this->actingAs($user)->json(
            'POST',
            "/auth/users/$id/change_password",
            ['password'=>$new_weak_password]
        );
        $response->assertResponseStatus(400);

        // strong password must be accepted
        $response = $this->actingAs($user)->json(
            'POST',
            "/auth/users/$id/change_password",
            ['password'=>$new_strong_password]
        );
        $response->assertResponseStatus(204);
    }

    /**
     * changing password oneself
     * @return void
     */
    public function testChangeMyPassword() {
        // create user
        $new_password = Str::random(16);
        $old_password = Str::random(16);
        $user = User::factory()->create([
            'password' => Hash::make($old_password)
        ]);

        $id = $user->id;

        $response = $this->actingAs($user)->json(
            'POST',
            "/auth/users/$id/change_password",
            ['password' => $new_password],
        );
        $response->assertResponseStatus(204);

        // old password is no longer valid
        $response = $this->json(
            'POST',
            '/auth/login',
            ['id'=>$id, 'password'=>$old_password]
        );
        $response->assertResponseStatus(401);

        // use new password instead old one
        $response = $this->json(
            'POST',
            '/auth/login',
            ['id'=>$id, 'password'=>$new_password]
        );
        $response->assertResponseOk();
    }

    /**
     * changing other's password
     * @return void
     */
    public function testChangeOthersPassword() {
        // create user
        $new_password = Str::random(16);
        $old_password = Str::random(16);
        $user = User::factory()->create([
            'password' => Hash::make($old_password)
        ]);
        $admin_user = User::factory()->create([
            'password' => Hash::make(Str::random(16)),
            'perm_admin' => 1
        ]);

        $id = $user->id;
        $admin_id = $admin_user->id;

        // [204] admin changes user's password
        $response = $this->actingAs($admin_user)->json(
            'POST',
            "/auth/users/$id/change_password",
            ['password' => $new_password],
        );
        $response->assertResponseStatus(204);

        // [403] user changes admin's password
        $response = $this->actingAs($user)->json(
            'POST',
            "/auth/users/$admin_id/change_password",
            ['password' => $new_password],
        );
        $response->assertResponseStatus(403);

        // old password is no longer valid
        $response = $this->json(
            'POST',
            '/auth/login',
            ['id'=>$id, 'password'=>$old_password]
        );
        $response->assertResponseStatus(401);

        // use new password instead of old one
        $response = $this->json(
            'POST',
            '/auth/login',
            ['id'=>$id, 'password'=>$new_password]
        );
        $response->assertResponseOk();
    }
}
