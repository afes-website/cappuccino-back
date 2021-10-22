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
 * - /guests/register-spare:post
 */
class RegisterSpareTest extends TestCase {

    /* Register-Spare */

    /**
     * RegisterSpare
     * Guest オブジェクトが返ってきている
     * is_spare = true
     */
    public function testRegister() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        Guest::factory()->for($reservation)->create();
        $spare_id = GuestFactory::createGuestId($reservation->term->guest_type);
        $this->actingAs($user)->post(
            '/guests/register-spare',
            ['guest_id' => $spare_id, 'reservation_id' => $reservation->id]
        );
        $this->assertResponseOk();
        $guest = Guest::find($spare_id);
        $this->seeJsonEquals([
            'id' => $guest->id,
            'registered_at' => $guest->registered_at,
            'revoked_at' => $guest->revoked_at,
            'exhibition_id' => $guest->exhibition_id,
            'is_spare' => true,
            'term' => [
                'id' => $guest->term->id,
                'enter_scheduled_time' => $guest->term->enter_scheduled_time->toIso8601String(),
                'exit_scheduled_time' => $guest->term->exit_scheduled_time->toIso8601String(),
                'guest_type' => $guest->term->guest_type,
                'class' => $guest->term->class,
            ]
        ]);
    }

    /**
     * 予約が存在しない
     * RESERVATION_NOT_FOUND
     */
    public function testReservationNotFound() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        Guest::factory()->for($reservation)->create();
        $spare_id = GuestFactory::createGuestId($reservation->term->guest_type);
        $this->actingAs($user)->post(
            '/guests/register-spare',
            ['guest_id' => $spare_id, 'reservation_id' => 'R-' . Str::random(7)]
        );
        $this->expectErrorResponse('RESERVATION_NOT_FOUND');
    }

    /**
     * 予約情報について、入場処理をまだ1度もしていない
     * NO_MEMBER_CHECKED_IN
     */
    public function testUnusedReservation() {
        $count = 5;

        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();

        $spare_id = GuestFactory::createGuestId($reservation->term->guest_type);

        $this->actingAs($user)->post(
            '/guests/register-spare',
            ['guest_id' => $spare_id, 'reservation_id' => $reservation->id]
        );
        $this->expectErrorResponse('NO_MEMBER_CHECKED_IN');
    }

    /**
     * 退場時間を過ぎている
     * EXIT_TIME_EXCEEDED
     */
    public function testExitTimeExceeded() {
        $user = User::factory()->permission('executive')->create();
        $term = Term::factory()->afterPeriod()->create();

        $reservation = Reservation::factory()->for($term)->create();
        Guest::factory()->for($reservation)->create();
        $spare_id = GuestFactory::createGuestId($reservation->term->guest_type);

        $this->actingAs($user)->post(
            '/guests/register-spare',
            ['guest_id' => $spare_id, 'reservation_id' => $reservation->id]
        );

        $this->expectErrorResponse('EXIT_TIME_EXCEEDED');
    }

    /**
     * GuestId の形式の誤り
     * INVALID_WRISTBAND_CODE
     * - {2文字でない}-{5文字でない} 形式のチェック
     * - 使用できない文字を使っていないかのチェック
     */
    public function testInvalidGuestCode() {
        $invalid_codes = [];
        $count = 5;

        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        Guest::factory()->for($reservation)->create();

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
                '/guests/register-spare',
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
        Guest::factory()->for($reservation)->create();

        $guest_id = GuestFactory::createGuestId($reservation->term->guest_type, 'XX'); //存在しない Prefix

        $this->actingAs($user)->post(
            '/guests/register-spare',
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
        Guest::factory()->for($reservation)->create();

        $guest_id = GuestFactory::createGuestId($reservation->term->guest_type);

        $guest_id = substr($guest_id, -1) . ($guest_id[-1] === '0' ? '1' : '0');

        $this->actingAs($user)->post(
            '/guests/register-spare',
            ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
        );

        $this->expectErrorResponse('INVALID_WRISTBAND_CODE');
    }

    /**
     * GuestId が使用済み
     * 異なる reservation で2回入場処理を行う
     */
    public function testAlreadyUsedGuestCode() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $guest_id = Guest::factory()->for($reservation)->for($reservation->term)->create()->id;
        $this->actingAs($user)->post(
            '/guests/register-spare',
            ['guest_id' => $guest_id, 'reservation_id' => $reservation->id]
        );
        $this->expectErrorResponse('ALREADY_USED_WRISTBAND');
    }

    public function multipleCase() {
        return Common::multipleArray(
            ['all_member_checked_in', 'no_member_checked_in'],
            ['after_period', 'in_period'],
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
            'all_member_checked_in',
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
                '/guests/register-spare',
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

    /**
     * 権限不足
     * Exhibition, Admin, ロール無し ですべて403が返ってくる事をチェック
     */
    public function testForbidden() {
        $users[] = User::factory()->permission('exhibition')->create();
        $users[] = User::factory()->permission('admin')->create(); // ADMIN perm doesnt mean all perm
        $users[] = User::factory()->create();

        foreach ($users as $user) {
            $this->actingAs($user)->post("guests/register-spare");
            $this->assertResponseStatus(403);
        }
    }

    /**
     * ログインしていない
     * 401
     */
    public function testGuest() {
        $this->post('/guests/register-spare');
        $this->assertResponseStatus(401);
    }
}
