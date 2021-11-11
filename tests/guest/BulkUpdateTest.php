<?php
namespace Tests\guest;

use App\Models\Exhibition;
use Database\Factories\GuestFactory;
use Faker\Factory;
use Tests\TestCase;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;

/**
 * - /guests/bulk-update:post
 */
class BulkUpdateTest extends TestCase {
    /**
     * OK が出る
     * 空の配列を渡しているので結果も空になっている
     */
    public function testResponseOk() {
        $user = User::factory()->permission('executive', 'exhibition')->create();
        $this->actingAs($user)->json('post', '/guests/bulk-update', [[]]);
        $this->assertResponseOk();
        $this->seeJson([]);
    }
}
