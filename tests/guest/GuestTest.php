<?php
namespace Tests\guest;

use Tests\TestCase;
use App\Models\Guest;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * - guest:get
 * - guest/$id:get
 */

class GuestTest extends TestCase {
    public function testGetAll() {
        $term_count = 3;
        $guest_count = 3;
        $user = User::factory()->permission('executive')->create();

        for ($i = 0; $i < $term_count; $i++) {
            Guest::factory()->count($term_count)->for(Term::factory())->count($guest_count)->create();
        }

        $this->actingAs($user)->get('/guests');
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());
        $res = json_decode($this->response->getContent());
        $this->assertCount($term_count * $guest_count, $res);
    }

    public function testShow() {
        $user = User::factory()->permission('executive')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->get("/guests/{$guest->id}");
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());

        $this->seeJsonEquals([
            'id' => $guest->id,
            'entered_at' => $guest->entered_at,
            'exited_at' => $guest->exited_at,
            'exhibition_id' => $guest->exhibition_id,
            'term' => [
                'id' => $guest->term->id,
                'enter_scheduled_time' => $guest->term->enter_scheduled_time->toIso8601String(),
                'exit_scheduled_time' => $guest->term->exit_scheduled_time->toIso8601String(),
                'guest_type' => $guest->term->guest_type
            ]
        ]);
    }

    public function testNotFound() {
        $user = User::factory()->permission('executive')->create();
        $id = Str::random(8);
        Guest::factory()->create();
        $this->actingAs($user)->get("guests/$id");

        $this->assertResponseStatus(404);
    }
}
