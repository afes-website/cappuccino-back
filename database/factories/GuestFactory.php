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

    public function createGuestId(string $guest_type): string {
        do {
            $id = config('cappuccino.guest_types')[$guest_type]['prefix'] . "-" . Str::random(5);
        } while (Guest::find($id));
        return $id;
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'term_id'=>Term::factory(),
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
