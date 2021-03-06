<?php
namespace Tests;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Guest;
use App\Resources\ReservationResource;
use App\Resources\TermResource;

/**
 * - /reservation/{id}:get
 * - /reservation/{id}/check:get
 * - /reservation/search:get
 */
class ReservationTest extends TestCase {

    /**
     * /reservation/{id}:get
     */
    public function testShow() {
        $reservation = Reservation::factory()->create();
        $member_count = rand(1, $reservation->member_all);
        Guest::factory()->for($reservation)->count($member_count)->create();

        $this->get("/reservations/{$reservation->id}");
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());
        $this->seeJsonEquals([
            'id' => $reservation->id,
            'term'=> new TermResource($reservation->term),
            'member_all' => $reservation->member_all,
            'member_checked_in' => $member_count,
        ]);
    }

    public function testNotFound() {
        $this->get("/reservations/R-00000000");
        $this->assertJson($this->response->getContent());
        $this->expectErrorResponse("RESERVATION_NOT_FOUND", 404);
    }

    /**
     * /reservation/{id}/check:get
     */
    public function testCheck() {
        $user = User::factory()->permission('reservation')->create();
        $reservation = Reservation::factory()->create();

        $this->actingAs($user)->get("/reservations/{$reservation->id}/check");
        $this->assertJson($this->response->getContent());
        $this->seeJsonEquals([
            'valid' => true,
            'error_code' => null,
            'reservation' => new ReservationResource($reservation)
        ]);
    }

    public function testCheckNotFound() {
        $user = User::factory()->permission('reservation')->create();
        $this->actingAs($user)->get("/reservations/R-00000000/check");
        $this->assertJson($this->response->getContent());
        $this->expectErrorResponse("RESERVATION_NOT_FOUND", 404);
    }

    /**
     * /reservation/search:get
     */
    public function testSearch() {
        $count = 5;
        $user = User::factory()->permission('reservation')->create();

        $reservation = Reservation::factory()->create();
        Reservation::factory()->count($count)->create();

        $this->actingAs($user)->json('GET', '/reservations/search', ['term_id' => $reservation->term->id]);
        $this->assertResponseOk();
        $this->assertCount(1, json_decode($this->response->getContent()));
    }
}
