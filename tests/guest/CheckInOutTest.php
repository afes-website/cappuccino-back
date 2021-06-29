<?php
namespace Tests\guest;

use Database\Factories\GuestFactory;
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

    const ID_CHARACTER = '234578acdefghijkmnprstuvwxyz';
    const PREFIX_LENGTH = 2;
    const ID_LENGTH = 5;

    private static function createGuestId(string $guest_type): string {
        return GuestFactory::createGuestId($guest_type);
    }

    /* Check-In */

    /**
     * CheckIn
     * Guestオブジェクトが返ってきている
     * @todo 返ってくるようにする
     */
    public function testCheckIn() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $guest_id = $this->createGuestId($reservation->term->guest_type);
        $this->actingAs($user)->post(
            '/guests/check-in',
            ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
        );
        $this->assertResponseOk();
    }


    /**
     * 複数人のCheckInができる事の確認
     */
    public function testCheckInMultiple() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $member_count = $reservation->member_all;

        for ($i = 0; $i < $member_count; $i++) {
            do {
                $guest_id = $this->createGuestId($reservation->term->guest_type);
            } while (Guest::find($guest_id));
            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
            );
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
        $guest_id = $this->createGuestId($term->guest_type);
        $this->actingAs($user)->post(
            '/guests/check-in',
            ['guest_id' => $guest_id, 'reservation_id' => 'R-' . Str::random(7)]
        );

        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('RESERVATION_NOT_FOUND', $code);
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
                $new_guest_id = $this->createGuestId($term->guest_type);
            } while (Guest::find($new_guest_id));

            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $new_guest_id, 'reservation_id' => $reservation->id]
            );

            $this->assertResponseStatus(400);
            $this->assertJson($this->response->getContent());
            $code = json_decode($this->response->getContent())->error_code;
            $this->assertEquals('ALL_MEMBER_CHECKED_IN', $code);
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
            $guest_id = $this->createGuestId($term[$i]->guest_type);
            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
            );

            $this->assertResponseStatus(400);
            $this->assertJson($this->response->getContent());
            $code = json_decode($this->response->getContent())->error_code;
            $this->assertEquals('OUT_OF_RESERVATION_TIME', $code);
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
            $character_count = strlen(self::ID_CHARACTER);
            for ($i = 0; $i < $id; $i++) {
                $code .= self::ID_CHARACTER[rand(0, $character_count - 1)];
            }
            $invalid_codes[] = Str::random($prefix) . '-' . $code;
        }
        do {
            $code = Str::random(self::PREFIX_LENGTH) . '-' . Str::random(self::ID_LENGTH);
        } while (preg_match(Guest::VALID_FORMAT, $code));

        $invalid_codes[] = $code;

        foreach ($invalid_codes as $invalid_code) {
            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $invalid_code, 'reservation_id' => $reservation->id]
            );
            $this->assertResponseStatus(400);
            $this->assertJson($this->response->getContent());
            $code = json_decode($this->response->getContent())->error_code;
            $this->assertEquals('INVALID_WRISTBAND_CODE', $code);
        }
    }

    /**
     * 使用するカラーが間違っている
     * prefix: XX を使用してチェック
     */
    public function testWrongWristbandColor() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $character_count = strlen(self::ID_CHARACTER);
        $id = '';
        for ($i = 0; $i < self::ID_LENGTH; $i++) {
            $id .= self::ID_CHARACTER[rand(0, $character_count - 1)];
        }
        $guest_id = "XX" . "-" . $id; //存在しない Prefix

        $this->actingAs($user)->post(
            '/guests/check-in',
            ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
        );

        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('WRONG_WRISTBAND_COLOR', $code);
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
            do {
                $guest_id = $this->createGuestId($term->guest_type);
            } while (in_array($guest_id, $used_id));
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

            $this->assertResponseStatus(400);
            $this->assertJson($this->response->getContent());
            $code = json_decode($this->response->getContent())->error_code;
            $this->assertEquals('ALREADY_USED_WRISTBAND', $code);
        }
    }

    public function testMultipleError() {
        foreach (Common::multipleArray(
            ['all_member_checked_in', 'no_member_checked_in'],
            ['after_period', 'before_period', 'in_period'],
            ['character_invalid', 'character_valid'],
            ['length_invalid', 'length_valid'],
            ['guest_used', 'guest_unused'],
            ['wrong_term', 'right_term']
        ) as $state
        ) {
            if ($state === [
                'no_member_checked_in',
                'in_period',
                'character_valid',
                'length_valid',
                'guest_unused',
                'right_term',
            ]) continue;

            DB::beginTransaction();
            try {
                $user = User::factory()->permission('admin', 'executive')->create();
                $member_count = rand(1, 10);
                $reservation = Reservation::factory()->state(['member_all' => $member_count]);
                $guest_code = substr(self::createGuestId('GuestBlue'), 0, -1);

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
                        $guest_code .= '9';
                        break;
                    case 'character_valid':
                        $guest_code .= '2';
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
                $this->assertResponseStatus(400);
            } catch (\Exception $e) {
                var_dump($state);
                throw $e;
            } finally {
                DB::rollBack();
            }
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

    /**
     * Guest が存在しない
     * その場で適当に生成した GuestId でテスト
     */
    public function testCheckOutGuestNotFound() {
        $user = User::factory()->permission('executive')->create();
        $term = Term::factory()->inPeriod()->create();
        $guest_id = $this->createGuestId($term->guest_type);

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
        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_EXITED', $code);
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
