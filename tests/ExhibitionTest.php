<?php
namespace Tests;

/*
 * guest/:get guest/$id:get
 */

use App\Models\Exhibition;
use App\Models\User;

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
}
