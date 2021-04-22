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
        $enter = $this->faker->dateTimeBetween('-2 days', '+2 days');
        return [
            'id' => $this->faker->unique()->realText(16),
            'enter_scheduled_time' => $enter,
            'exit_scheduled_time' => $this->faker->dateTimeBetween($enter, '+2 days'),
            'guest_type' => array_rand(config('cappuccino.guest_types'))
        ];
    }

    public function inPeriod() {
        return $this->state([
            'enter_scheduled_time' => $this->faker->dateTimeBetween('-2 days', '-1 sec'),
            'exit_scheduled_time' => $this->faker->dateTimeBetween('+1 sec', '+2 days'),
        ]);
    }

    public function beforePeriod() {
        $enter = $this->faker->dateTimeBetween('+1 sec', '+2 days');
        return $this->state([
            'enter_scheduled_time' => $enter,
            'exit_scheduled_time' => $this->faker->dateTimeBetween($enter, '+2 days'),
        ]);
    }

    public function afterPeriod() {
        $exit = $this->faker->dateTimeBetween('-2 days', '-1 sec');
        return $this->state([
            'enter_scheduled_time' => $this->faker->dateTimeBetween('-2 days', $exit),
            'exit_scheduled_time' => $exit,
        ]);
    }
}
