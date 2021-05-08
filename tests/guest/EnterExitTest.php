<?php
namespace Tests\guest;

use App\Models\Exhibition;
use Tests\TestCase;
use App\Models\Guest;
use App\Models\Term;
use App\Models\User;
use Faker\Provider\DateTime;

/*
 * guests/enter, guests/$id/exit:post
 */
class EnterExitTest extends TestCase {

    public function testEnter() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseOk();
    }

    public function testGuestNotFound() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/GB-00000/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(404);
        $this->receiveJson();
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_NOT_FOUND', $code);
    }


    public function testAlreadyEntered() {
        $user = User::factory()
            ->permission('exhibition')
            ->has(Exhibition::factory())
            ->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseOk();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(400);
        $this->receiveJson();
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_ENTERED', $code);
    }

    public function testPeopleLimitExceeded() {
        $guest_count = 5;
        $exhibition = Exhibition::factory()
            ->has(Guest::factory()->count($guest_count))
            ->state(['limit' => $guest_count]);
        $user = User::factory()->permission('exhibition')->has($exhibition)->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseStatus(400);
        $this->receiveJson();
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('PEOPLE_LIMIT_EXCEEDED', $code);
    }

    public function testAlreadyExited() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/check-out",
        );

        $this->assertResponseOk();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(400);
        $this->receiveJson();
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_EXITED', $code);
    }

    public function testExitTimeExceeded() {
        $term = Term::factory()->state([
            'enter_scheduled_time' => DateTime::dateTimeBetween('-1 year', '-1 day'),
            'exit_scheduled_time' => DateTime::dateTimeBetween('-1 year', '-1 day')
        ]);
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->has($term)->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseStatus(400);
        $this->receiveJson();
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('EXIT_TIME_EXCEEDED', $code);
    }

    public function testExhibitionNotFound() {
        $user = User::factory()->permission('exhibition')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(400);
        $this->receiveJson();
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('EXHIBITION_NOT_FOUND', $code);
    }

    public function testExit() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->state(['exhibition_id'=>$user->id])->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/exit",
        );
        $this->assertResponseOk();
    }

    public function testExitGuestNotFound() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/GB-00000/exit",
        );

        $this->assertResponseStatus(404);
        $this->receiveJson();
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_NOT_FOUND', $code);
    }
    public function testExitAlreadyExited() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/exit",
        );

        $this->assertResponseOk();

        $this->actingAs($user)->post(
            "/guests/$guest->id/exit",
        );

        $this->assertResponseStatus(400);
        $this->receiveJson();
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_EXITED', $code);
    }

    public function testExitExhibitionNotFound() {
        $user = User::factory()->permission('exhibition')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/exit",
        );
    }

    public function testForbidden() {
        $users[] = User::factory()->permission('executive')->create();
        $users[] =  User::factory()->permission('admin')->create(); // ADMIN perm doesnt mean all perm
        $users[] = User::factory()->create();

        $paths = [
            "/guests/GB_00000000/enter", "/guests/GB_00000000/exit"
        ];

        foreach ($users as $user) {
            foreach ($paths as $path) {
                $this->actingAs($user)->post($path);
                $this->assertResponseStatus(403);
            }
        }
    }

    public function testGuest() {
        $guest_id = Guest::factory()->create()->id;
        $paths = [
            "/guests/$guest_id/enter", "/guests/$guest_id/exit"
        ];

        foreach ($paths as $path) {
            $this->post($path);
            $this->assertResponseStatus(401);
        }
    }
}
