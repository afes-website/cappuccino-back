<?php

namespace Database\Factories;

use App\Models\Exhibition;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Guest::class;

    public static function createGuestId(string $guest_type = null, string $prefix = null): string {
        if ($guest_type === null) {
            $guest_type = array_rand(config('cappuccino.guest_types'));
        }
        if ($prefix === null) $prefix = config('cappuccino.guest_types')[$guest_type]['prefix'];

        do {
            $character_count = strlen(Guest::VALID_CHARACTER);
            $id = '';
            for ($i = 0; $i < Guest::ID_LENGTH - 1; $i++) {
                $id .= Guest::VALID_CHARACTER[rand(0, $character_count - 1)];
            }
            $guest_id = $prefix . "-" . $id . Guest::calculateParity($id);
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
            'registered_at'=>$this->faker->dateTime,
            'revoked_at'=>null,
            'exhibition_id'=>Exhibition::factory(),
        ];
    }
}
