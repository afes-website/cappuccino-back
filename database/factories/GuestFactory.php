<?php

namespace Database\Factories;

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

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'id'=>$this->faker->userName,
            'entered_at'=>$this->faker->dateTime,
            'exited_at'=>null,
            'exh_id'=>null,
            'term_id'=>Term::factory(),
            'reservation_id'=>Reservation::factory(),
        ];
    }
}
