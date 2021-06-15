<?php
namespace Tests\guest;

use Database\Factories\GuestFactory;
use Tests\TestCase;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Term;
use App\Models\User;
use Faker\Provider\DateTime;
use Illuminate\Support\Str;

/**
 * - /guests/check-in:post
 * - /guests/$id/check-out:post
 */

class CheckInOutTest extends TestCase {

    private static function createGuestId(string $guest_type): string {
        return GuestFactory::createGuestId($guest_type);
    }

    /* Check-In */

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

    public function testAlreadyEnteredReservation() {
        $count = 5;

        $user = User::factory()->permission('executive')->create();
        $term = Term::factory()->inPeriod()->create();
        $used_id = [];
        for ($i = 0; $i < $count; ++$i) {
            $reservation = Reservation::factory()->for($term)->create();

            do {
                $guest_id_1 = $this->createGuestId($term->guest_type);
            } while (in_array($guest_id_1, $used_id));
            $used_id[] = $guest_id_1;

            do {
                $guest_id_2 = $this->createGuestId($term->guest_type);
            } while (in_array($guest_id_2, $used_id));
            $used_id[] = $guest_id_2;

            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_id_1, 'reservation_id' => $reservation->id]
            );

            $this->assertResponseOk();

            $this->actingAs($user)->post(
                '/guests/check-in',
                ['guest_id' => $guest_id_1, 'reservation_id' => $reservation->id]
            );

            $this->assertResponseStatus(400);
            $this->assertJson($this->response->getContent());
            $code = json_decode($this->response->getContent())->error_code;
            $this->assertEquals('ALREADY_ENTERED_RESERVATION', $code);
        }
    }

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
            $invalid_codes[] = Str::random($prefix) . '-' . Str::random($id);
        }

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

    public function testWrongWristbandColor() {
        $user = User::factory()->permission('executive')->create();
        $reservation = Reservation::factory()->create();
        $id_len = 5;
        $valid_character = '234578acdefghijkmnprstuvwxyz';
        $character_count = strlen($valid_character);
        $id = '';
        for ($i = 0; $i < $id_len; $i++) {
            $id .= $valid_character[rand(0, $character_count - 1)];
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

    /* Check-Out */

    public function testCheckOut() {
        $user = User::factory()->permission('executive')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/check-out",
        );
        $this->assertResponseOk();
    }

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

    public function testAlreadyExited() {
        $user = User::factory()->permission('executive')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/check-out",
        );
        $this->actingAs($user)->post(
            "/guests/$guest->id/check-out",
        );
        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_EXITED', $code);
    }

    public function testForbidden() {
        $users[] = User::factory()->permission('exhibition')->create();
        $users[] =  User::factory()->permission('admin')->create(); // ADMIN perm doesnt mean all perm
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
