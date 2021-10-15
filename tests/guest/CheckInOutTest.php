<?php
namespace Tests\guest;

use Database\Factories\GuestFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Common;

/**
 * - /guests/check-in:post
 * - /guests/$id/check-out:post
 */
class CheckInOutTest extends TestCase {

    /* Check-In */

    /**
     * CheckIn
     * Guestオブジェクトが返ってきている
     */
    public function testCheckIn() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $guest_id = GuestFactory::createGuestId($reservation->term->guest_type);
        $this->actingAs($user)->post(
            '/guests/check-in',
            ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
        );
        $this->assertResponseOk();
        $guest = Guest::find($guest_id);
        $this->seeJsonEquals([
            'id' => $guest->id,
            'registered_at' => $guest->registered_at,
            'revoked_at' => $guest->revoked_at,
            'exhibition_id' => $guest->exhibition_id,
            'is_spare' => false,
            'term' => [
                'id' => $guest->term->id,
                'enter_scheduled_time' => $guest->term->enter_scheduled_time->toIso8601String(),
                'exit_scheduled_time' => $guest->term->exit_scheduled_time->toIso8601String(),
                'guest_type' => $guest->term->guest_type
            ]
        ]);
    }

    /**
     * 複数人 CheckIn
     */
    public function testCheckInMultiple() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $member_count = $reservation->member_all;

        for ($i = 1; $i <= $member_count; $i++) {
            $guest_id = GuestFactory::createGuestId($reservation->term->guest_type);
            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
            );
            $this->assertEquals($i, $reservation->guest()->count());
            $this->assertResponseOk();
        }
    }

    /**
     * 予約が存在しない
     * RESERVATION_NOT_FOUND
     */
    public function testReservationNotFound() {
        $user = User::factory()->permission('executive')->create();
        $term = Term::factory()->inPeriod()->create();
        $guest_id = GuestFactory::createGuestId($term->guest_type);
        $this->actingAs($user)->post(
            '/guests/check-in',
            ['guest_id' => $guest_id, 'reservation_id' => 'R-' . Str::random(7)]
        );
        $this->expectErrorResponse('RESERVATION_NOT_FOUND');
    }

    //   INVALID_RESERVATION_INFO: NO TEST

    /**
     * すでに入場済み
     * ALL_MEMBER_CHECKED_IN
     * 入場処理を2回行ってチェック
     */
    public function testAlreadyEnteredReservation() {
        $count = 5;

        $user = User::factory()->permission('executive')->create();
        $term = Term::factory()->inPeriod()->create();
        for ($i = 0; $i < $count; ++$i) {
            $member_count = rand(1, 10);
            $reservation = Reservation::factory()->for($term)->state(['member_all' => $member_count])->create();

            Guest::factory()->for($reservation)->count($member_count)->create();

            do {
                $new_guest_id = GuestFactory::createGuestId($term->guest_type);
            } while (Guest::find($new_guest_id));

            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $new_guest_id, 'reservation_id' => $reservation->id]
            );
            $this->expectErrorResponse('ALL_MEMBER_CHECKED_IN');
        }
    }

    /**
     * 予約時間外
     * OUT_OF_RESERVATION_TIME
     * 入場可能時間前、時間後それぞれでエラーが発生する事をチェック
     */
    public function testOutOfReservationTime() {
        $user = User::factory()->permission('executive')->create();
        $term[0] = Term::factory()->afterPeriod()->create();

        $term[1] = Term::factory()->beforePeriod()->create();

        for ($i = 0; $i < 2; $i++) {
            $reservation = Reservation::factory()->for($term[$i])->create();
            $guest_id = GuestFactory::createGuestId($term[$i]->guest_type);
            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
            );

            $this->expectErrorResponse('OUT_OF_RESERVATION_TIME');
        }
    }

    /**
     * GuestIdの形式の誤り
     * INVALID_WRISTBAND_CODE
     * - {2文字でない}-{5文字でない} 形式のチェック
     * - 使用できない文字を使っていないかのチェック
     */
    public function testInvalidGuestCode() {
        $invalid_codes = [];
        $count = 5;

        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();

        for ($i = 0; $i < $count; ++$i) {
            // $prefix !== 2 && $id !== 5 となるように変数の値を決定する
            do {
                $prefix = rand(1, 10);
                $id = rand(1, 10);
            } while ($prefix === 2 && $id === 5);
            $code = '';
            $character_count = strlen(Guest::VALID_CHARACTER);
            for ($i = 0; $i < $id; $i++) {
                $code .= Guest::VALID_CHARACTER[rand(0, $character_count - 1)];
            }
            $invalid_codes[] = Str::random($prefix) . '-' . $code;
        }
        do {
            $code = Str::random(Guest::PREFIX_LENGTH) . '-' . Str::random(Guest::ID_LENGTH);
        } while (preg_match(Guest::VALID_FORMAT, $code));

        $invalid_codes[] = $code;

        foreach ($invalid_codes as $invalid_code) {
            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $invalid_code, 'reservation_id' => $reservation->id]
            );
            $this->expectErrorResponse('INVALID_WRISTBAND_CODE');
        }
    }

    /**
     * 使用するカラーが間違っている
     * prefix: XX を使用してチェック
     */
    public function testWrongWristbandColor() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();

        $guest_id = GuestFactory::createGuestId($reservation->term->guest_type, 'XX'); //存在しない Prefix

        $this->actingAs($user)->post(
            '/guests/check-in',
            ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
        );

        $this->expectErrorResponse('WRONG_WRISTBAND_COLOR');
    }

    /**
     * チェックサムが誤っている
     */
    public function testCheckSumFail() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();

        $guest_id = GuestFactory::createGuestId($reservation->term->guest_type);

        $guest_id = substr($guest_id, -1) . ($guest_id[-1] === '0' ? '1' : '0');

        $this->actingAs($user)->post(
            '/guests/check-in',
            ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
        );

        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('INVALID_WRISTBAND_CODE', $code);
    }

    /**
     * GuestId が使用済み
     * 異なる reservation で2回入場処理を行う
     */
    public function testAlreadyUsedGuestCode() {
        $count = 5;

        $user = User::factory()->permission('executive')->create();
        $term = Term::factory()->inPeriod()->create();
        $used_id = [];
        for ($i = 0; $i < $count; ++$i) {
            $reservation_1 = Reservation::factory()->for($term)->create();
            $reservation_2 = Reservation::factory()->for($term)->create();
            $guest_id = GuestFactory::createGuestId($term->guest_type);
            $used_id[] = $guest_id;

            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_id, 'reservation_id' => $reservation_1->id]
            );

            $this->assertResponseOk();

            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_id, 'reservation_id' => $reservation_2->id]
            );

            $this->expectErrorResponse('ALREADY_USED_WRISTBAND');
        }
    }

    public function multipleCase() {
        return Common::multipleArray(
            ['all_member_checked_in', 'no_member_checked_in'],
            ['after_period', 'before_period', 'in_period'],
            ['character_invalid', 'character_valid'],
            ['length_invalid', 'length_valid'],
            ['guest_used', 'guest_unused'],
            ['wrong_term', 'right_term']
        );
    }

    /**
     * @dataProvider multipleCase
     */
    public function testMultipleError(...$state) {
        if ($state === [
            'no_member_checked_in',
            'in_period',
            'character_valid',
            'length_valid',
            'guest_unused',
            'right_term',
        ]) {
            $this->assertTrue(true);
            return;
        }

            DB::beginTransaction();
        try {
            $user = User::factory()->permission('admin', 'executive')->create();
            $member_count = rand(1, 10);
            $reservation = Reservation::factory()->state(['member_all' => $member_count]);
            $guest_code = GuestFactory::createGuestId('GuestBlue');

            switch ($state[0]) {
                case 'all_member_checked_in':
                    $reservation = $reservation
                        ->has(Guest::factory()
                            ->for(Term::factory()->create())
                            ->count($member_count));
                    break;
                case 'no_member_checked_in':
                    break;
            }
            $term = Term::factory()->state(['guest_type' => 'GuestBlue']);
            switch ($state[1]) {
                case 'after_period':
                    $term = $term->afterPeriod();
                    break;
                case 'before_period':
                    $term = $term->beforePeriod();
                    break;
                case 'in_period':
                    $term = $term->inPeriod();
                    break;
            }
            $term = $term->create();
            switch ($state[2]) {
                case 'character_invalid':
                    $guest_code[-1] = 'z';
                    break;
                case 'character_valid':
                    break;
            }
            switch ($state[3]) {
                case 'length_invalid':
                    $guest_code .= '2';
                    break;
                case 'length_valid':
                    break;
            }
            switch ($state[4]) {
                case 'guest_used':
                    Guest::factory()->state(['id' => $guest_code])->for(Term::factory()->create())->create();
                    break;
                case 'guest_unused':
                    break;
            }
            switch ($state[5]) {
                case 'wrong_term':
                    $guest_code[1] = 'X';
                    break;
                case 'right_term':
                    break;
            }

            $reservation = $reservation->for($term)->create();

            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_code, 'reservation_id' => $reservation->id]
            );
            $this->expectErrorResponse();
        } catch (\Exception $e) {
            var_dump($state);
            throw $e;
        } finally {
            DB::rollBack();
        }
    }

    /* Check-Out */

    /**
     * checkOut テスト
     * 200が返ってきている
     */
    public function testCheckOut() {
        $user = User::factory()->permission('executive')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/{$guest->id}/check-out",
        );
        $this->assertResponseOk();
    }

    public function guestSpareProvider(): array {
        return [['guest'], ['spare']];
    }

    /**
     * Force Revoke のテスト
     * - 予約人数分に退場した時に余分数が ForceRevoke される
     *  - Spare かどうかを問わない
     *  - revoked_at に時刻が入る
     *  - is_force_revoked = true
     * @dataProvider guestSpareProvider
     */
    public function testForceRevoke($to_revoke) {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $member_all = $reservation->member_all;
        Guest::factory()->for($reservation)->count($member_all - 1)->create(['revoked_at' => Carbon::now()]);

        $guest = Guest::factory()->for($reservation)->create();
        $spare = Guest::factory()->for($reservation)->create(['is_spare' => true]);
        $other_spares = Guest::factory()->count(2)->for($reservation)->create(['is_spare' => true]);

        if ($to_revoke === 'guest') {
            $this->actingAs($user)->post(
                "/guests/{$guest->id}/check-out",
            );
            $data = Guest::find($spare->id);
        } else {
            $this->actingAs($user)->post(
                "/guests/{$spare->id}/check-out",
            );
            $data = Guest::find($guest->id);
        }
        $this->assertJson($this->response->getContent());
        $this->assertResponseOk();
        $this->assertTrue($data->is_force_revoked == 1);
        $this->assertNotNull($data->revoked_at);

        foreach ($other_spares as $i) {
            $data = Guest::find($i->id);
            $this->assertTrue($data->is_force_revoked == 1);
            $this->assertNotNull($data->revoked_at);
        }
    }

    /**
     * まだ 場内に人が残っていなるなら Force Revoke は行わない
     * @dataProvider guestSpareProvider
     */
    public function testGuestRest($to_revoke) {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $member_all = $reservation->member_all;
        Guest::factory()->for($reservation)->count($member_all - 2)->create(['revoked_at' => Carbon::now()]);
        Guest::factory()->for($reservation)->create();
        $guest = Guest::factory()->for($reservation)->create();
        $spare = Guest::factory()->for($reservation)->create(['is_spare' => true]);
        $other_spares = Guest::factory()->count(2)->for($reservation)->create(['is_spare' => true]);

        if ($to_revoke === 'guest') {
            $this->actingAs($user)->post(
                "/guests/{$guest->id}/check-out",
            );
            $data = Guest::find($spare->id);
        } else {
            $this->actingAs($user)->post(
                "/guests/{$spare->id}/check-out",
            );
            $data = Guest::find($guest->id);
        }
        $this->assertResponseOk();
        $this->assertFalse($data->is_force_revoked == 1);
        $this->assertNull($data->revoked_at);

        foreach ($other_spares as $i) {
            $data = Guest::find($i->id);
            $this->assertFalse($data->is_force_revoked == 1);
            $this->assertNull($data->revoked_at);
        }
    }

    /**
     * Guest が存在しない
     * その場で適当に生成した GuestId でテスト
     */
    public function testCheckOutGuestNotFound() {
        $user = User::factory()->permission('executive')->create();
        $term = Term::factory()->inPeriod()->create();
        $guest_id = GuestFactory::createGuestId($term->guest_type);

        $this->actingAs($user)->post(
            "/guests/$guest_id/check-out",
            ['guest_id' => $guest_id]
        );
        $this->assertResponseStatus(404);
    }

    /**
     * すでに退場済み
     * 2回処理をしてチェック
     */
    public function testAlreadyExited() {
        $user = User::factory()->permission('executive')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/{$guest->id}/check-out",
        );
        $this->actingAs($user)->post(
            "/guests/{$guest->id}/check-out",
        );
        $this->expectErrorResponse('GUEST_ALREADY_CHECKED_OUT');
    }

    /**
     * 権限不足
     * Exhibition, Admin, ロール無し ですべて403が返ってくる事をチェック
     */
    public function testForbidden() {
        $users[] = User::factory()->permission('exhibition')->create();
        $users[] = User::factory()->permission('admin')->create(); // ADMIN perm doesnt mean all perm
        $users[] = User::factory()->create();

        $paths = [
            "/guests/GB_00000000/check-out", '/guests/check-in',
        ];

        foreach ($users as $user) {
            foreach ($paths as $path) {
                $this->actingAs($user)->post($path);
                $this->assertResponseStatus(403);
            }
        }
    }

    /**
     * ログインしていない
     * 401
     */
    public function testGuest() {
        $guest_id = Guest::factory()->create()->id;
        $paths = [
            "/guests/$guest_id/check-out", '/guests/check-in',
        ];

        foreach ($paths as $path) {
            $this->post($path);
            $this->assertResponseStatus(401);
        }
    }
}
