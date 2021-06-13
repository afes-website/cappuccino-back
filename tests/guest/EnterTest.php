<?php
namespace Tests\guest;

use App\Models\ActivityLogEntry;
use App\Models\Exhibition;
use Database\Factories\GuestFactory;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Guest;
use App\Models\Term;
use App\Models\User;

/**
 * guests/$id/enter:post
 */
class EnterTest extends TestCase {

    /**
     * 展示入室のテスト
     * Guest の滞在中の展示が更新されている
     * ActivityLog が生成されている
     */
    public function testEnter() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseOk();
        $raw_guest = json_decode($this->response->getContent());
        $this->assertEquals($user->exhibition->id, $raw_guest->exhibition_id);
        $this->assertTrue(
            ActivityLogEntry::query()
                ->where('guest_id', $guest->id)
                ->where('exhibition_id', $user->exhibition->id)
                ->exists()
        );
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

    /**
     * 権限チェック
     * executive, 権限なし のユーザーの実行時に 403 が返ってきている
     */
    public function testPermission() {
        $users[] = User::factory()->permission('executive')->create();
        $users[] = User::factory()->create();

        foreach ($users as $user) {
            $this->actingAs($user)->post("/guests/GB_00000000/enter");
            $this->assertResponseStatus(403);
        }
        
        //TODO: Admin の時は任意の展示を触れて、Exhibitionは自分のしか触れない
    }

    /**
     * PEOPLE_CAPACITY_EXCEEDED
     * 展示の滞在者数制限に達している
     * 定員ぴったりのときと定員を超えている
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
     * GUEST_NOT_FOUND
     * Guest が存在しないとき
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
     * Guest が既に退場処理をしているとき
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
     * ログインチェック
     * ログインしていないときに 403 が返ってきている
     */
    public function testGuest() {
        $guest_id = Guest::factory()->create()->id;

        $this->post("/guests/$guest_id/enter");
        $this->assertResponseStatus(401);
    }
}