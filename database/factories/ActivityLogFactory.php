<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Exhibition;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'timestamp'=>$this->faker->dateTime,
            'exhibition_id'=>Exhibition::factory(),
            'guest_id'=>Guest::factory(),
            'log_type'=>$this->faker->randomElement(['exit','enter'])
        ];
    }
}
