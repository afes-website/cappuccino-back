<?php
namespace Tests\guest;

use App\Models\Exhibition;
use Database\Factories\GuestFactory;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Guest;
use App\Models\Term;
use App\Models\User;
use Faker\Provider\DateTime;

/**
 * guests/$id/enter:post
 */
class EnterTest extends TestCase {

    public function testEnter() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseOk();
        // TODO: Guest が返ってくるか
    }

    public function testGuestNotFound() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        Guest::factory()->create();
        do {
            $guest_id = GuestFactory::createGuestId();
        } while (Guest::find($guest_id));
        $this->actingAs($user)->post(
            "/guests/{$guest_id}/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(404);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_NOT_FOUND', $code);

        $guest_id = Str::random(8);
        $this->actingAs($user)->post(
            "/guests/{$guest_id}/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(404);
        $this->assertJson($this->response->getContent());
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
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_ENTERED', $code);
    }

    public function testPeopleLimitExceeded() {
        $guest_count = 5;
        $exhibition = Exhibition::factory()
            ->has(Guest::factory()->count($guest_count))
            ->state(['capacity' => $guest_count]);
        $user = User::factory()->permission('exhibition')->has($exhibition)->create();
        $guest = Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('PEOPLE_LIMIT_EXCEEDED', $code);
    }

    public function testAlreadyExited() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $executive_user = User::factory()->permission('executive')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($executive_user)->post(
            "/guests/$guest->id/check-out",
        );

        $this->assertResponseOk();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_EXITED', $code);
    }

    public function testExitTimeExceeded() {
        $term = Term::factory()->afterPeriod();
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->for($term)->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/enter",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
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
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('EXHIBITION_NOT_FOUND', $code);
    }

    public function testForbidden() {
        $users[] = User::factory()->permission('executive')->create();
        $users[] = User::factory()->create();

        foreach ($users as $user) {
            $this->actingAs($user)->post("/guests/GB_00000000/enter");
            $this->assertResponseStatus(403);
        }
    }

    public function testGuest() {
        $guest_id = Guest::factory()->create()->id;

        $this->post("/guests/$guest_id/enter");
        $this->assertResponseStatus(401);
    }
}
