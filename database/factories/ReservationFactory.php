<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'id'=>$this->faker->userName,
            'people_count'=>$this->faker->numberBetween(1, 100),
            'name'=>$this->faker->name,
            'term_id'=>Term::factory(),
            'email'=>$this->faker->email,
            'address'=>$this->faker->address,
            'cellphone'=>$this->faker->phoneNumber
        ];
    }
}
