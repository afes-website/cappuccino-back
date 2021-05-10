<?php
namespace Tests\guest;

use App\Models\Exhibition;
use Database\Factories\GuestFactory;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Guest;
use App\Models\Term;
use App\Models\User;
use Faker\Provider\DateTime;

/**
 * guests/$id/enter:post
 */
class EnterTest extends TestCase {

    /**
     * 展示入室のテスト
     * Guest オブジェクトが返ってきている
     */
    public function testEnter() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseOk();
        // TODO: Guest が返ってくるか
    }

    /**
     * Guest が存在しないとき
     * GUEST_NOT_FOUND が返ってきている
     */
    public function testGuestNotFound() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        Guest::factory()->create();
        do {
            $guest_id = GuestFactory::createGuestId();
        } while (Guest::find($guest_id));
        $this->actingAs($user)->post(
            "/guests/{$guest_id}/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(404);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_NOT_FOUND', $code);

        $guest_id = Str::random(8);
        $this->actingAs($user)->post(
            "/guests/{$guest_id}/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(404);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_NOT_FOUND', $code);
    }

    /**
     * Guest が既に展示に入室しているとき
     * GUEST_ALREADY_ENTERED が返ってきている
     */
    public function testAlreadyEntered() {
        $user = User::factory()
            ->permission('exhibition')
            ->has(Exhibition::factory())
            ->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseOk();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_ENTERED', $code);
    }

    /**
     * 展示の滞在者数制限に達している
     * PEOPLE_LIMIT_EXCEEDED が返ってきている
     */
    public function testPeopleLimitExceeded() {
        $guest_count = 5;
        $exhibition = Exhibition::factory()
            ->has(Guest::factory()->count($guest_count))
            ->state(['capacity' => $guest_count]);
        $user = User::factory()->permission('exhibition')->has($exhibition)->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('PEOPLE_LIMIT_EXCEEDED', $code);
    }

    /**
     * Guest が既に退場処理をしている
     * GUEST_ALREADY_EXITED が返ってきている
     */
    public function testAlreadyExited() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $executive_user = User::factory()->permission('executive')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($executive_user)->post(
            "/guests/$guest->id/check-out",
        );

        $this->assertResponseOk();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_EXITED', $code);
    }

    /**
     * Guest が退場予定時間を過ぎている
     * EXIT_TIME_EXCEEDED が返ってきている
     */
    public function testExitTimeExceeded() {
        $term = Term::factory()->afterPeriod();
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->for($term)->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('EXIT_TIME_EXCEEDED', $code);
    }

    /**
     * Exhibition が存在しない
     * EXHIBITION_NOT_FOUND が返ってきている
     */
    public function testExhibitionNotFound() {
        $user = User::factory()->permission('exhibition')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('EXHIBITION_NOT_FOUND', $code);
    }

//    public function testExhibitionParam() {
//        //TODO: Admin の時は任意の展示を触れて、Exhibitionは自分のしか触れない
//    }

    /**
     * 権限チェック
     * executive, 権限なし のユーザーの実行時に 403 が返ってきている
     */
    public function testForbidden() {
        $users[] = User::factory()->permission('executive')->create();
        $users[] = User::factory()->create();

        foreach ($users as $user) {
            $this->actingAs($user)->post("/guests/GB_00000000/enter");
            $this->assertResponseStatus(403);
        }
    }

    /**
     * ログインチェック
     * ログインしていないときに 403 が返ってきている
     */
    public function testGuest() {
        $guest_id = Guest::factory()->create()->id;

        $this->post("/guests/$guest_id/enter");
        $this->assertResponseStatus(401);
    }
}
