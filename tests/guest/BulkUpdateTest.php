<?php
namespace Tests\guest;

use App\Models\ActivityLogEntry;
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

    public function testApplied() {
        $user = User::factory()->has(Exhibition::factory())->permission('executive', 'exhibition')->create();
        $reservation = Reservation::factory()->create();
        $factory = Factory::create();
        $this->actingAs($user)->json('post', '/guests/bulk-update', [
            [
                'command' => 'check-in',
                'reservation_id' => $reservation->id,
                'guest_id' => GuestFactory::createGuestId(),
                'timestamp' => $factory->dateTime()->format("Y-m-d H:i:s"),
            ],
            [
                'command' => 'enter',
                'guest_id' => Guest::factory()->create()->id,
                'timestamp' => $factory->dateTime()->format("Y-m-d H:i:s"),
            ],
            [
                'command' => 'exit',
                'guest_id' => Guest::factory()->create()->id,
                'timestamp' => $factory->dateTime()->format("Y-m-d H:i:s"),
            ],
            [
                'command' => 'check-out',
                'guest_id' => Guest::factory()->create()->id,
                'timestamp' => $factory->dateTime()->format("Y-m-d H:i:s"),
            ],
            [
                'command' => 'register-spare',
                'guest_id' => GuestFactory::createGuestId(),
                'reservation_id' => $reservation->id,
                'timestamp' => $factory->dateTime()->format("Y-m-d H:i:s"),
            ],
        ]);
        $this->assertResponseOk();
        $this->seeJsonEquals([
            ['is_applied' => true, 'code' => null],
            ['is_applied' => true, 'code' => null],
            ['is_applied' => true, 'code' => null],
            ['is_applied' => true, 'code' => null],
            ['is_applied' => true, 'code' => null],
        ]);
        $this->assertCount(5, ActivityLogEntry::all()->where('verified', false));
        $this->assertCount(5, Guest::all());
    }

    private function caseGenerator() {
        $factory = Factory::create();
        $command = $factory->randomElement(['enter', 'exit', 'check-in', 'check-out', 'register-spare', 'dummy']);
        if ($factory->boolean()) $guest_id = Guest::factory()->create()->id;
        else $guest_id = GuestFactory::createGuestId();

        if ($factory->boolean()) $reservation_id = Reservation::factory()->create()->id;
        else $reservation_id = $factory->name();

        if ($factory->boolean) $timestamp = $factory->dateTime();
        else $timestamp = $factory->dateTimeBetween('+1 day', '+30 years');

        return [
            'command' => $command,
            'guest_id' => $guest_id,
            'reservation_id' => $reservation_id,
            'timestamp' => $timestamp->format("Y-m-d H:i:s"),
        ];
    }

    public function testRandom() {
        $user = User::factory()->has(Exhibition::factory())->permission('executive', 'exhibition')->create();
        $count = 8;
        $requests = [];
        for ($i = 0; $i < $count; $i++) {
            $requests[] = $this->caseGenerator();
        }
        $this->actingAs($user)->json('post', '/guests/bulk-update', $requests);
        $this->assertResponseOk();
        $this->assertJson($this->response->getContent());
        $res = json_decode($this->response->getContent());
        $this->assertCount($count, $res);
        $this->seeJsonStructure(array_fill($count, 0, ['is_applied', 'code']), $res);
    }
}
