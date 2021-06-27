<?php
namespace Tests;

use App\Models\Reservation;
use App\Models\Term;
use App\Models\User;
use App\Resources\ReservationResource;
use App\Resources\TermResource;

/**
 * - /terms:get
 */
class ReservationTest extends TestCase {

    /**
     * Term についてのデータの中身が正しい
     */
    public function testShow() {
        $user = User::factory()->permission('reservation')->create();
        $reservation = Reservation::factory()->create();
        $this->actingAs($user)->get("/reservations/{$reservation->id}");
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());
        $this->seeJsonEquals([
            'id' => $reservation->id,
            'term'=> new TermResource($reservation->term),
            'member_all' => $reservation->member_count,
            'member_checked_in' => 0,
        ]);
    }

    /**
     * 存在する全タームが返ってきている
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

    /**
     * 権限チェック
     */
    public function testCheck() {
        $user = User::factory()->permission('reservation')->create();
        $reservation = Reservation::factory()->create();
        $this->actingAs($user)->get("/reservations/{$reservation->id}/check");
        $this->assertResponseOk();

        $this->assertJson($this->response->getContent());
        $this->seeJsonEquals([
            'valid' => true,
            'error_code' => null,
            'reservation' => new ReservationResource($reservation)
        ]);
    }
}
