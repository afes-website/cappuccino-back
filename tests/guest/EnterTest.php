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
 * - guests/$id/enter:post
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
     * - executive, 権限なし の場合は指定できない
     * - admin 権限があれば任意の展示を指定できる
     * - exhibition 権限のみのときは自分の展示のみ指定できる
     * 上記のルールに違反したときに 403 が、そうでない場合は正しく処理がされている事を確認する
     */
    public function testPermission() {
        $not_permitted_users[] = User::factory()->permission('executive')->create();
        $not_permitted_users[] = User::factory()->create();

        foreach ($not_permitted_users as $user) {
            $this->actingAs($user)->post("/guests/GB_00000000/enter");
            $this->assertResponseStatus(403);
        }

        $admin_users[] = User::factory()->permission('admin')->has(Exhibition::factory())->create();
        $admin_users[] = User::factory()->permission('admin', 'exhibition')->has(Exhibition::factory())->create();
        $other_exhibition = Exhibition::factory()->create();

        foreach ($admin_users as $user) {
            foreach ([true, false] as $mode) {
                $guest = Guest::factory()->create();
                $exh_id = $mode === true ? $user->id : $other_exhibition->id;
                $this->actingAs($user)->post(
                    "/guests/$guest->id/enter",
                    ['exhibition_id' => $exh_id]
                );
                $this->assertResponseOk();
            }
        }

        $exhibition_user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();

        $guest = Guest::factory()->create();
        $this->actingAs($exhibition_user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $exhibition_user->id]
        );
        $this->assertResponseOk();

        $guest = Guest::factory()->create();
        $this->actingAs($exhibition_user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $other_exhibition->id]
        );
        $this->assertResponseStatus(403);
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
            $id = GuestFactory::createGuestId();
        } while (Guest::find($id));
        $guests_id[] = $id;
        $guests_id[] = Str::random(8);

        foreach ($guests_id as $guest_id) {
            $this->actingAs($user)->post(
                "/guests/{$guest_id}/enter",
                ['exhibition_id' => $user->id]
            );

            $this->assertResponseStatus(404);
            $this->assertJson($this->response->getContent());
            $code = json_decode($this->response->getContent())->error_code;
            $this->assertEquals('GUEST_NOT_FOUND', $code);
        }
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
     * Guest が他展示に入室しているとき
     * 正しく実行される
     * ActivityLog は 前にいた展示の Exit は生成されず、新しい展示の Enter だけ生成されている
     */
    public function testGuestInOtherExhibition() {
        $user = User::factory()
            ->permission('admin')
            ->has(Exhibition::factory())
            ->create();
        $exhibition_id = Exhibition::factory()->create()->id;

        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $exhibition_id]
        );
        $this->assertResponseOk();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseOk();

        // 前に居た展示の Exit log が勝手に生成されていない
        $this->assertFalse(
            ActivityLogEntry::query()
                ->where('guest_id', $guest->id)
                ->where('exhibition_id', $exhibition_id)
                ->where('log_type', 'exit')
                ->exists()
        );

        // Enter の log は生成されている
        $this->assertTrue(
            ActivityLogEntry::query()
                ->where('guest_id', $guest->id)
                ->where('exhibition_id', $user->exhibition->id)
                ->where('log_type', 'enter')
                ->exists()
        );
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
