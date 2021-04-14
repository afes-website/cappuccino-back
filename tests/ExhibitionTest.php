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
        $this->assertCount($count, $res);
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
            'limit' => $exhibition->limit,
            'count' => [],
        ]);
    }
}
