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

    public function testListFilter() {
        $count = 5;

        $user = User::factory()->permission('reservation')->create();

        $log = ActivityLog::factory()->count($count)->create();
        foreach ([
            'id',
            'timestamp',
            'guest_id',
            'log_type',
            'reservation_id',
            'exh_id',
        ] as $key) {
            if ($key === 'exh_id') {
                $item = $log[0]->{'exhibition_id'};
            } else {
                $item = $log[0]->{$key};
            }
            $this->call('GET', '/log', [$key => $item]);
            $this->assertResponseOk();

            $this->receiveJson();
            $ret_articles = json_decode($this->response->getContent());
            foreach ($ret_articles as $ret_article) {
                $this->assertEquals($ret_article->{$key}, $item);
            }
        }
    }
}
