<?php
namespace Tests;

/*
 * log/
 */

use App\Resources\GuestResource;
use App\Models\ActivityLogEntry;
use App\Models\User;

class LogTest extends TestCase {
    public function testData() {
        $user = User::factory()->permission('executive')->create();
        $log = ActivityLogEntry::factory()->create();

        $this->actingAs($user)->get('/log');
        $this->assertResponseOk();
        $this->receiveJson();
        $this->seeJsonEquals([
            [
                'id' => $log->id,
                'timestamp' => $log->timestamp->toIso8601String(),
                'exhibition_id' => $log->exhibition->id,
                'guest' => new GuestResource($log->guest),
                'log_type' => $log->log_type,
            ],
        ]);
    }

    public function testCount() {
        $count = 5;
        ActivityLogEntry::factory()->count($count)->create();
        $user = User::factory()->permission('executive')->create();

        $this->actingAs($user)->get('/log');
        $this->assertResponseOk();
        $this->receiveJson();
        $this->assertCount($count, json_decode($this->response->getContent()));
    }

    public function testListFilter() {
        $count = 5;

        $user = User::factory()->permission('reservation')->create();

        $log = ActivityLogEntry::factory()->count($count)->create();
        foreach ([
            'id',
            'timestamp',
            'guest_id',
            'log_type',
            'reservation_id',
            'exhibition_id',
        ] as $key) {
            $item = $log[0]->{$key};
            $this->call('GET', '/log', [$key => $item]);
            $this->assertResponseOk();

            $this->receiveJson();
            $ret_articles = json_decode($this->response->getContent());
            foreach ($ret_articles as $ret_article) {
                $this->assertEquals($ret_article->{$key}, $item);
            }
        }
    }

    public function testGetPermission() {
        foreach (['executive', 'exhibition', 'reservation'] as $perm) {
            $user = User::factory()->permission($perm)->create();

            $this->actingAs($user)->get('/log');
            $this->assertResponseOk();
        }

        foreach (['admin', 'teacher'] as $perm) {
            $user = User::factory()->permission($perm)->create();
            $this->actingAs($user)->get('/log');
            $this->assertResponseStatus(403);
        }
    }

    public function testFilterPermission() {
        foreach (['executive', 'exhibition', 'reservation'] as $perm) {
            $user = User::factory()->permission($perm)->create();
            $log = ActivityLogEntry::factory()->create();
            foreach ([
                'id',
                'timestamp',
                'guest_id',
                'log_type',
                'reservation_id',
            ] as $key) {
                $this->actingAs($user)->get('/log', [$key => $log->{$key}]);
                $this->assertResponseOk();
            }

            $this->actingAs($user)->get('/log', ['exhibition_id' => $log->exhibition_id]);
            if ($perm === 'reservation') $this->assertResponseOk();
            else $this->assertResponseStatus(403);
        }
    }
}
