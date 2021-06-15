<?php

namespace Database\Factories;

use App\Models\Exhibition;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GuestFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Guest::class;

    public static function createGuestId(string $guest_type = null): string {
        if ($guest_type === null) {
            $guest_type = array_rand(config('cappuccino.guest_types'));
        }
        do {
            $character_count = strlen(Guest::VALID_CHARACTER);
            $id = '';
            for ($i = 0; $i < Guest::ID_LENGTH; $i++) {
                $id .= Guest::VALID_CHARACTER[rand(0, $character_count - 1)];
            }
            $guest_id = config('cappuccino.guest_types')[$guest_type]['prefix'] . "-" . $id;
        } while (Guest::find($guest_id));
        return $guest_id;
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'term_id'=>Term::factory()->inPeriod(),
            'reservation_id'=>Reservation::factory(),
            'id'=> function (array $attributes) {
                $guest_type = Term::find($attributes['term_id'])->guest_type;
                return $this->createGuestId($guest_type);
            },
            'entered_at'=>$this->faker->dateTime,
            'exited_at'=>null,
            'exhibition_id'=>Exhibition::factory(),
        ];
    }
}
