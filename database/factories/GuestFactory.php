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
            $digits_sum = 0;
            for ($i = 0; $i < Guest::ID_LENGTH - 1; $i++) {
                $d = Guest::VALID_CHARACTER[rand(0, $character_count - 1)];
                $id .= $d;
                $digits_sum += hexdec($d) * (1 + ($i % 2) * 2);
            }
            $id .= dechex($digits_sum % 0x10);
            $guest_id = $prefix . "-" . $id;
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
