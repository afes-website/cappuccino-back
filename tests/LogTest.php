<?php
namespace Tests;

/*
 * log/
 */

use App\Resources\GuestResource;
use App\Models\ActivityLog;
use App\Models\User;

class LogTest extends TestCase {
    public function testData() {
        $user = User::factory()->permission('executive')->create();
        $log = ActivityLog::factory()->create();

        $this->actingAs($user)->get('/log');
        $this->assertResponseOk();
        $this->receiveJson();
        $this->seeJsonEquals([
            [
                'id' => $log->id,
                'timestamp' => $log->timestamp->toIso8601ZuluString(),
                'exh_id' => $log->exhihibion->id,
                'guest' => new GuestResource($user),
                'log_type' => $log->log_type,
            ],
        ]);
    }

    public function testCount() {
        $count = 5;
        ActivityLog::factory()->count($count)->create();
        $user = User::factory()->permission('executive')->create();

        $this->actingAs($user)->get('/log');
        $this->assertResponseOk();
        $this->receiveJson();
        $this->assertCount($count, json_decode($this->response->getContent()));
    }
}
