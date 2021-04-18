<?php
namespace Tests;

/*
 * guest/:get guest/$id:get
 */

use App\Models\Exhibition;
use App\Models\Guest;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

class ExhibitionTest extends TestCase {
    public function testGetAll() {
        $count = 3;
        $user = User::factory()->permission('exhibition')->create();
        Exhibition::factory()->count($count)->create();

        $this->actingAs($user)->get('/exhibitions');
        $this->assertResponseOk();
        $this->receiveJson();
        $res = json_decode($this->response->getContent());
        $this->assertCount($count, get_object_vars($res->exh));
    }

    public function testShowInfo() {
        $user = User::factory()->permission('exhibition')->create();
        $exhibition = Exhibition::factory()->create();

        $this->actingAs($user)->get("/exhibitions/$exhibition->id");
        $this->assertResponseOk();
        $this->receiveJson();

        $this->seeJsonContains([
            'info' => [
                'room_id' => $exhibition->room_id,
                'name' => $exhibition->name,
                'thumbnail_image_id' => $exhibition->thumbnail_image_id,
            ],
            'limit' => $exhibition->capacity,
            'count' => [],
        ]);
    }

    public function testShowCount() {
        $guest_count = 10;
        $term_count = 3;
        $term = Term::factory()->count(3)->create();
        $exhibition = Exhibition::factory()
            ->has(
                Guest::factory()
                    ->count($guest_count * $term_count)
                    ->state(new Sequence(
                        ['term_id' => $term[0]->id],
                        ['term_id' => $term[1]->id],
                        ['term_id' => $term[2]->id]
                    ))
            )->create();
        $user = User::find($exhibition->id);

        $this->actingAs($user)->get("/exhibitions/$exhibition->id");
        $this->assertResponseOk();
        $this->receiveJson();

        $this->seeJsonContains([
            'count' => [
                $term[0]->id => $guest_count,
                $term[1]->id => $guest_count,
                $term[2]->id => $guest_count,
            ]
        ]);
    }

    public function testCountExited() {
        $guest_count = 10;
        $exhibition = Exhibition::factory()
            ->has(
                Guest::factory()
                    ->count($guest_count*2)
                    ->state(new Sequence([], ['exited_at' => Carbon::now()]))
            )->create();
        $user = User::find($exhibition->id);

        $this->actingAs($user)->get("/exhibitions/$exhibition->id");
        $this->assertResponseOk();
        $this->receiveJson();

        $this->assertCount($guest_count, json_decode($this->response->getContent()));
    }

    public function testDontShowEmptyTerm() {
        $guest_count = 10;
        $exhibition = Exhibition::factory()->create(
            Guest::factory()->count($guest_count*2)
        );
        $user = User::find($exhibition->id);
        $term = Term::factory()->create();

        $this->actingAs($user)->get("/exhibitions/$exhibition->id");
        $this->assertResponseOk();

        $this->seeJsonDoesntContains([
            $term->id => 0
        ]);
    }

    public function testNotFound() {
        $user = User::factory()->permission('exhibition')->create();
        $id = Str::random(8);
        Guest::factory()->create();
        $this->actingAs($user)->get("/exhibitions/$id");

        $this->assertResponseStatus(404);
    }
}
