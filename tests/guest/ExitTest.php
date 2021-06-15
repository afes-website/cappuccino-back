<?php
namespace Tests\guest;

use App\Models\ActivityLogEntry;
use App\Models\Exhibition;
use Database\Factories\GuestFactory;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Guest;
use App\Models\Term;
use App\Models\User;
use Faker\Provider\DateTime;

/**
 * - /guests/$id/exit:post
 */

class ExitTest extends TestCase {
    public function testExit() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $guest = Guest::factory()->state(['exhibition_id'=>$user->id])->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/exit",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseOk();

        $raw_guest = json_decode($this->response->getContent());
        $this->assertNull($raw_guest->exhibition_id);
        $this->assertTrue(
            ActivityLogEntry::query()
                ->where('guest_id', $guest->id)
                ->where('exhibition_id', $user->exhibition->id)
                ->exists()
        );
    }

    public function testExhibitionNotFound() {
        $user = User::factory()->permission('exhibition')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($user)->post(
            "/guests/$guest->id/exit",
            ['exhibition_id' => $user->id]
        );
        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('EXHIBITION_NOT_FOUND', $code);
    }

    public function testPermission() {
        $not_permitted_users[] = User::factory()->permission('executive')->create();
        $not_permitted_users[] = User::factory()->create();

        foreach ($not_permitted_users as $user) {
            $this->actingAs($user)->post("/guests/GB_00000000/exit");
            $this->assertResponseStatus(403);
        }

        $admin_users[] = User::factory()->permission('admin')->has(Exhibition::factory())->create();
        $admin_users[] = User::factory()->permission('admin', 'exhibition')->has(Exhibition::factory())->create();
        $other_exhibition = Exhibition::factory()->create();

        foreach ($admin_users as $user) {
            foreach ([true, false] as $mode) {
                $guest = Guest::factory()->create();
                $exh_id = $mode === true ? $user->id : $other_exhibition->id;
                $this->actingAs($user)->post(
                    "/guests/$guest->id/exit",
                    ['exhibition_id' => $exh_id]
                );
                $this->assertResponseOk();
            }
        }

        $exhibition_user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();

        $guest = Guest::factory()->create();
        $this->actingAs($exhibition_user)->post(
            "/guests/$guest->id/exit",
            ['exhibition_id' => $exhibition_user->id]
        );
        $this->assertResponseOk();

        $guest = Guest::factory()->create();
        $this->actingAs($exhibition_user)->post(
            "/guests/$guest->id/exit",
            ['exhibition_id' => $other_exhibition->id]
        );
        $this->assertResponseStatus(403);
    }

    public function testGuestNotFound() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        Guest::factory()->create();
        $this->actingAs($user)->post(
            "/guests/GB-00000/exit",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(404);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_NOT_FOUND', $code);
    }

    public function testAlreadyExited() {
        $user = User::factory()->permission('exhibition')->has(Exhibition::factory())->create();
        $executive_user = User::factory()->permission('executive')->create();
        $guest = Guest::factory()->create();

        $this->actingAs($executive_user)->post(
            "/guests/$guest->id/check-out"
        );

        $this->assertResponseOk();

        $this->actingAs($user)->post(
            "/guests/$guest->id/exit",
            ['exhibition_id' => $user->id]
        );

        $this->assertResponseStatus(400);
        $this->assertJson($this->response->getContent());
        $code = json_decode($this->response->getContent())->error_code;
        $this->assertEquals('GUEST_ALREADY_EXITED', $code);
    }

    public function testNoMatterWhereGuestIsIn() {
        $user = User::factory()
            ->permission('admin')
            ->has(Exhibition::factory())
            ->create();
        $exhibition_id = Exhibition::factory()->create()->id;

        $guests[] = Guest::factory()->state(['exhibition_id' => $exhibition_id])->create();
        $guests[] = Guest::factory()->state(['exhibition_id' => $user->id])->create();
        $guests[] = Guest::factory()->state(['exhibition_id' => null])->create();

        foreach ($guests as $guest) {
            $this->actingAs($user)->post(
                "/guests/$guest->id/exit",
                ['exhibition_id' => $user->id]
            );
            $this->assertResponseOk();

            // Exit の log が生成されている
            $this->assertTrue(
                ActivityLogEntry::query()
                    ->where('guest_id', $guest->id)
                    ->where('exhibition_id', $user->id)
                    ->where('log_type', 'exit')
                    ->exists()
            );
        }
    }

    public function testGuest() {
        $guest_id = Guest::factory()->create()->id;

        $this->post("/guests/$guest_id/exit");
        $this->assertResponseStatus(401);
    }
}
