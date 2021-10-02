<?php
namespace Tests;

use App\Models\Exhibition;
use App\Models\Guest;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Str;

/**
 * - /guest/:get
 * - /guest/$id:get
 */
class ExhibitionTest extends TestCase {

    /**
     * 全展示の情報が返ってきている
     */
    public function testGetAll() {
        $count = 3;
        $user = User::factory()->create();
        Exhibition::factory()->count($count)->create();

        $this->actingAs($user)->get('/exhibitions');
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());
        $res = json_decode($this->response->getContent());
        $this->assertCount($count, get_object_vars($res->exhibition));
    }

    /**
     * Guest の人数状況が Term 別に正しく集計されている
     */
    public function testAllCounts() {
        $guest_count = 10;
        $term_count = 3;
        $exh_count = 2;
        $term = Term::factory()->count(3)->create();
        $user = User::factory()->create();
        $my_exh = Exhibition::factory()
            ->has(
                Guest::factory()
                    ->count($guest_count * $term_count)
                    ->state(new Sequence(
                        ['term_id' => $term[0]->id],
                        ['term_id' => $term[1]->id],
                        ['term_id' => $term[2]->id]
                    ))
            )->for($user)->create();

        $exhibitions = Exhibition::factory()
            ->has(
                Guest::factory()
                    ->count($guest_count * $term_count)
                    ->state(new Sequence(
                        ['term_id' => $term[0]->id],
                        ['term_id' => $term[1]->id],
                        ['term_id' => $term[2]->id]
                    ))
            )->count($exh_count - 1)->create();
        $all_capacity = $exhibitions[0]->capacity + $my_exh->capacity;

        $this->actingAs($user)->get("/exhibitions");
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());

        $this->seeJsonContains([
            'all' => [
                'count' => [
                    $term[0]->id => $guest_count * $exh_count,
                    $term[1]->id => $guest_count * $exh_count,
                    $term[2]->id => $guest_count * $exh_count,
                ],
                'capacity' => $all_capacity
            ]
        ]);
    }

    /**
     * 各展示について叩いたときに Document 通りの内容のものが返ってきている
     */
    public function testShowInfo() {
        $user = User::factory()->create();
        $exhibition = Exhibition::factory()->create();

        $this->actingAs($user)->get("/exhibitions/{$exhibition->id}");
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());

        $this->seeJsonContains([
            'info' => [
                'room_id' => $exhibition->room_id,
                'name' => $exhibition->name,
                'thumbnail_image_id' => $exhibition->thumbnail_image_id,
            ],
            'capacity' => $exhibition->capacity,
            'count' => [],
        ]);
    }

    /**
     * 展示内にいる Guest がターム別に正しく集計されている
     */
    public function testShowCount() {
        $guest_count = 10;
        $term_count = 3;
        $term = Term::factory()->count(3)->create();
        $user = User::factory()->create();
        $exhibition = Exhibition::factory()
            ->has(
                Guest::factory()
                    ->count($guest_count * $term_count)
                    ->state(new Sequence(
                        ['term_id' => $term[0]->id],
                        ['term_id' => $term[1]->id],
                        ['term_id' => $term[2]->id]
                    ))
            )->for($user)->create();

        $this->actingAs($user)->get("/exhibitions/{$exhibition->id}");
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());

        $this->seeJsonContains([
            'count' => [
                $term[0]->id => $guest_count,
                $term[1]->id => $guest_count,
                $term[2]->id => $guest_count,
            ]
        ]);
    }

    /**
     * 退場済みの Guest はカウントされていない
     */
    public function testCountExited() {
        $guest_count = 10;
        $user = User::factory()->create();
        $term = Term::factory()->create();
        $exhibition = Exhibition::factory()
            ->has(
                Guest::factory()
                    ->for($term)
                    ->count($guest_count*2)
                    ->state(new Sequence([], ['exited_at' => Carbon::now()]))
            )
            ->for($user)
            ->create();

        $this->actingAs($user)->get("/exhibitions/{$exhibition->id}");
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());

        $this->assertEquals($guest_count, json_decode($this->response->getContent())->count->{$term->id});
    }

    /**
     * Guest が 1人もいない Term は表示されない
     */
    public function testDontShowEmptyTerm() {
        $guest_count = 10;
        $user = User::factory()->create();
        Exhibition::factory()
            ->for($user)
            ->has(Guest::factory()->count($guest_count*2))
            ->create();
        $term = Term::factory()->create();

        $this->actingAs($user)->get("/exhibitions/{$user->id}");
        $this->assertResponseOk();

        $this->seeJsonDoesntContains([
            $term->id => 0
        ]);
    }

    /**
     * 存在しない展示を選んだとき
     * 404
     */
    public function testNotFound() {
        $user = User::factory()->create();
        $id = Str::random(8);
        Guest::factory()->create();
        $this->actingAs($user)->get("/exhibitions/$id");

        $this->assertResponseStatus(404);
        $this->seeJsonEquals([
            'code' => 404,
            'error_code' => "EXHIBITION_NOT_FOUND"
        ]);
    }
}
