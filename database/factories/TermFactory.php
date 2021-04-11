<?php

namespace Database\Factories;

use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class TermFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Term::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'id' => $this->faker->realText(16),
            'enter_scheduled_time' => $this->faker->dateTimeBetween('-1year', '-1hour'),
            'exit_scheduled_time' => $this->faker->dateTimeBetween('+1hour', '+1year'),
            'guest_type' => array_rand(config('cappuccino.guest_types'))
        ];
    }
}
