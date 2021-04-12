<?php

namespace Database\Factories;

use App\Models\Exhibition;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExhibitionFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Exhibition::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'id'=>$this->faker->userName,
            'room_id'=>$this->faker->userName,
            'capacity'=> $this->faker->numberBetween(1),
            'updated_at'=>$this->faker->dateTime,
        ];
    }
}
