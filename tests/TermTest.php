<?php
namespace Tests;

use App\Models\Term;
use App\Models\User;

/**
 * - /terms:get
 */
class TermTest extends TestCase {

    /**
     * Term についてのデータの中身が正しい
     */
    public function testData() {
        $user = User::factory()->permission('executive')->create();
        $term = Term::factory()->create();

        $this->actingAs($user)->get('/terms');
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());
        $id = $term->id;
        $this->seeJsonEquals([
            $id => [
                'id' => $term->id,
                'enter_scheduled_time' => $term->enter_scheduled_time->toIso8601String(),
                'exit_scheduled_time' => $term->exit_scheduled_time->toIso8601String(),
                'guest_type' => $term->guest_type,
                'class' => $term->class,
            ],
        ]);
    }

    /**
     * 存在する全タームが返ってきている
     */
    public function testCount() {
        $count = 5;
        Term::factory()->count($count)->create();
        $user = User::factory()->permission('executive')->create();

        $this->actingAs($user)->get('/terms');
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());
        $this->assertCount($count, get_object_vars(json_decode($this->response->getContent())));
    }

    /**
     * 権限チェック
     */
    public function testGetPermission() {
        foreach (['executive', 'exhibition'] as $perm) {
            $user = User::factory()->permission($perm)->create();

            $this->actingAs($user)->get('/terms');
            $this->assertResponseOk();
        }

        foreach (['admin', 'teacher', 'reservation'] as $perm) {
            $user = User::factory()->permission($perm)->create();
            $this->actingAs($user)->get('/terms');
            $this->assertResponseStatus(403);
        }
    }
}
